<?php
/*
Plugin Name: Utilities for MTG
Description: Small plugin to display Magic The Gathering cards in images or links. Can generate 15 card booster packs from a csv file to be displayed. Display your cube in style!
Version:     1.4.0
Author:      Martin Ekström
*/

add_action('wp_enqueue_scripts', 'add_styles');
function add_styles() {
    wp_register_style('utilitiesformtgcss', plugins_url('styles.css',__FILE__));
    wp_enqueue_style('utilitiesformtgcss');

    wp_register_script('utilitiesformtgjs', plugins_url('utilitiesformtg.js',__FILE__));
    wp_enqueue_script('utilitiesformtgjs');
}

function ufmtg_call_api($method, $url, $data = false)
{
    usleep (50); //Scryfall wants delays between requests not to overload server

    $result = wp_remote_retrieve_body(wp_remote_get($url));

    $parsed_result = json_decode($result, true);
    if (isset($parsed_result['status'])) {
        if ($parsed_result['status'] == 404) {
            return "Card not found in Scryfall.";
        } elseif ($parsed_result['status'] == 503) {
            return "Error retrieving cards, Scryfall unavailable.";
        } elseif ($parsed_result['status'] == 500) {
            return "Error retrieving cards, Scryfall internal error.";
        }      
    }

    return $result;
}

function ufmtg_get_set_from_scryfall($set_code) {
    $search_url = "https://api.scryfall.com/cards/search?unique=cards&q=e%3A" . $set_code . "+is%3Abooster+-t%3Abasic";

    $result = ufmtg_call_api("get", $search_url);
    $parsed_result = json_decode($result, true);

    if (isset($parsed_result['has_more'])) {
        $has_more_result = ufmtg_call_api("get", $parsed_result['next_page']);
        $parsed_more_result = json_decode($has_more_result, true);
        return array_merge($parsed_result['data'], $parsed_more_result['data']);
    }

    return $parsed_result['data'];
}

/**
 * This function can extract strings from a string by sending in what is before and after said string.
 * Used to extract all [scrylink]card[/scrylink] tags in a html table to send a request and cache the cards beforehand.
 *
 * extractCardsFromString(htmlTable, "[scrylink]", "[/scrylink]")
 */
function extractCardsFromString($string, $start, $slut) {
    $cards = explode("|", $string);

    $search_url = "https://api.scryfall.com/cards/search?unique=cards&q=";

    $cards_list = [];
    foreach ($cards as $card) {
        if (strpos($card, ':')) {
            $cardnameAndSet = explode(':', $card);
            $cards_list[] = $cardnameAndSet[0] . "+e%3A" . $cardnameAndSet[1];
        } else {
            $cards_list[] = $card;
        }
    }
    $cards_to_add = '!'.implode('+or+!', $cards_list);
    $search_url .= $cards_to_add;
    $search_url = str_replace(array(' ', "'"), '', $search_url);

    $result = ufmtg_call_api("get", $search_url);
    $parsed_result = json_decode($result, true);
    $data = $parsed_result['data'];

    wp_cache_set("ufmtg_precached", $data);
}


/**
 * This function is made to list an amount of cards by asking for them in a list separated by :
 *
 * [p1p1deck]serra angel:cancel:grunn, the lonely king:disfigure[/p1p1deck]
 */
function ufmtg_build_deck($atts, $content = null) {
    return ufmtg_get_list_from_scryfall($content);
}

/**
 * Function to get image html for a list of cards separated by :
 */
function ufmtg_get_list_from_scryfall($cardlist, $exact = false) {
    if (is_array($cardlist)) {
        $cards = $cardlist;
    } else {
        $cards = explode(":", $cardlist);
    }

    $data = ufmtg_get_data_from_scryfall($cards, $exact);

    $cardimages = "";
    if (is_array($data)) {
        foreach ($data as $card) {
            $cardimages .= ufmtg_build_img($card);
        }
    }

    return $cardimages;
}

function ufmtg_get_data_from_scryfall(array $cards, $exact)
{
    $search_url = "https://api.scryfall.com/cards/search?unique=cards&q=";

    $glue = '+or+';
    if ($exact) {
        $glue .= '!';
    }
    $card_list = implode($glue, $cards);
    if ($exact) {
        $card_list = '!'.$card_list;
    }
    $card_list = str_replace(array(' ', "'"), '', $card_list);

    $search_url .= $card_list;

    $result = ufmtg_call_api("get", $search_url);
    $parsed_result = json_decode($result, true);

    return $parsed_result['data'];
}

/**
 * Create a scryfall search query for a card, can be accompanied by a certain set by adding :set in the cardname, f.ex Cancel:xln
 */
function ufmtg_create_scryfall_url($name_of_card) {
    $name = $name_of_card;
    $setCode = "";

    //Check if setcode was given
    if (strpos($name, ':') !== false) {
        $explodedString = explode(":", $name);
        $setCode = $explodedString[1];
        $setCode = "&set=" . $setCode;
    }
    $cardname = str_replace(array(" ", "'"), array("+", ""), $name);
    if (strpos($cardname, ':') !== false) {
        $cardname = substr($cardname, 0, strpos($cardname, ':'));
    }
    return "https://api.scryfall.com/cards/named?fuzzy=$cardname$setCode";
}

/**
 * Get a cached card from the wordpress cache
 */
function ufmtg_get_cached_card($name_of_card) {
    //Check if setcode was given
    $set_code = "";
    $cardname = "";
    $set = array();
    if (strpos($name_of_card, ':') !== false) {
        $explodedString = explode(":", $name_of_card);
        $set_code = $explodedString[1];
        $cardname = $explodedString[0];
    } else {
        $cardname = $name_of_card;
    }
    if (wp_cache_get("ufmtg_precached")) {
        $set = wp_cache_get("ufmtg_precached");
        if (!checkForCardInCache($set, $cardname)) {
            return checkForCardInCache($set, $cardname);
        }
    } elseif ($set_code) {
        if (wp_cache_get($set_code)) {
            $set = wp_cache_get($set_code);
        } else {
            $set = ufmtg_get_set_from_scryfall($set_code);
            wp_cache_set($set_code, $set);
        }
    }

    return checkForCardInCache($set, $cardname);
}

function checkForCardInCache($set, $cardname) {
    $cardfound = "false";
    if (is_array($set)) {
        foreach ($set as $cachedcard) {
            if (isset($cachedcard['name'])) {
                if(stripos($cardname, $cachedcard['name'])!==false) {
                    $cardfound = $cachedcard;
                }
            }
        }
    }
    return $cardfound;
}

/**
 * Create the html element for a card image
 */
function ufmtg_build_img($inputcard = "", $front = "", $back = "") {
    if ($front !== "" && $back !== "") {
        $id = mt_rand(10000000, 99999999);

        return '<span class="scryfall_hover-zoom--container"><img id="'. $id . '" src="' . $front . '" class="clickable scryfall_hover-zoom" onclick="swapimage('. $id . ',\'' . $front . '\',\'' . $back .'\');" /></span>';
    }
    if (isset($inputcard['card_faces'])) {
        $front = $inputcard['card_faces'][0]['image_uris']['normal'];
        $back = $inputcard['card_faces'][1]['image_uris']['normal'];

        $id = mt_rand(10000000, 99999999);

        return '<span class="scryfall_hover-zoom--container"><img id="'. $id . '" src="' . $front . '" class="clickable scryfall_hover-zoom" onclick="swapimage('. $id . ',\'' . $front . '\',\'' . $back .'\');" /></span>';
    }

    $front = $inputcard['image_uris']['normal'];

    return '<img src="' . $front . '" class="scryfall_hover-zoom" />';
}

/**
 * Create the html element for a card link
 */
function ufmtg_build_link(array $inputcard)
{
    $realcardname = $inputcard['name'];
    $cardlink = $inputcard['scryfall_uri'];
    if (!$cardlink) {
        return 'Card not found';
    }

    if (isset($inputcard['card_faces'])) {
        $front = $inputcard['card_faces'][0]['image_uris']['normal'];
        $back = $inputcard['card_faces'][1]['image_uris']['normal'];

        return '<span class="scryfall_hover_img"><a href="' . $cardlink . '" target="_blank">' . $realcardname . '<span><img src="' . $front . '" class="scryfall_card-hover-size"/><img src="' . $back . '" class="scryfall_card-hover-size"/></span></a></span>';
    }

    $front = $inputcard['image_uris']['normal'];

    return '<span class="scryfall_hover_img"><a href="' . $cardlink . '" target="_blank">' . $realcardname . '<span><img src="' . $front . '" class="scryfall_card-hover-size"/></span></a></span>';
}


/**
 * Function to get an image from scryfall just by typing a cardname
 * [scryimg set="xln"]gishath[scryimg]
 */
function ufmtg_get_scryfall_image($atts, $content = null)
{
    $cardlist = ufmtg_get_cached_card($content);
    if (is_string($cardlist)) {
        $result = ufmtg_call_api("get", ufmtg_create_scryfall_url($content));
        $cardlist = json_decode($result, true);
    }

    return ufmtg_build_img($cardlist);
}

function ufmtg_get_scryfall_link($atts, $content = null)
{
    $cardlist = ufmtg_get_cached_card($content);
    if (is_string($cardlist)) {
        $result = ufmtg_call_api("get", ufmtg_create_scryfall_url($content));
        $cardlist = json_decode($result, true);
    }

    return ufmtg_build_link($cardlist);
}

/**
 * This function is made to extract 15 cards from a list of cards in a CSV file online
 * It needs a url to a CSV file published online and the column number where the cardnames are located
 *
 * [p1p1cube column="2"]https://example.com/mycube.csv[/p1p1cube]
 */
function ufmtg_randomize_card_pack($atts, $content = null) {
    $spreadsheet_url = $content;
    $column = (int)$atts['column'] -1; //minus 1 is to make it more intuitive for users, colum A=1, B=2 etc.
    $cube = array();

    if(!ini_set('default_socket_timeout', 15)) {
        echo "unable to change socket timeout";
    }

    if (($handle = fopen($spreadsheet_url, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $spreadsheet_data[] = $data;
        }
        fclose($handle);
    } else {
        die("Problem reading csv");
    }

    //Get the cards from the right column of the spreadsheet
    foreach ($spreadsheet_data as $card1) {
        $cube[] = $card1[$column];
    }

    //Shuffle them up so they aren't sorted
    $random_pack = array_rand($cube, 15);
    shuffle($random_pack);

    $cardpack = "";
    foreach($random_pack as $cardnum) {
        $cardpack = $cardpack . $cube[$cardnum] . ":";
    }
    $cardpack = substr($cardpack, 0, -1);

    return ufmtg_get_list_from_scryfall($cardpack, true);
}

/**
 * [mtglink]
 */
function ufmtg_get_mtg_link($atts, $content = null) {
    $cardName = $atts['name'];
    $cardLink = $atts['url'];
    $cardBackSide = $atts['backside'] ?: "";
    if ($cardBackSide !== "") {
        return '<span class="scryfall_hover_img"><a href="' . $cardLink . '" target="_blank">' . $cardName . '<span><img src="' . $content . '" class="scryfall_card-hover-size"/><img src="' . $cardBackSide . '" class="scryfall_card-hover-size"/></span></a></span>';
    }

    return '<span class="scryfall_hover_img"><a href="' . $cardLink . '" target="_blank">' . $cardName . '<span><img src="' . $content . '" class="scryfall_card-hover-size"/></span></a></span>';
}

/**
 * [mtgimg]
 */
function ufmtg_get_mtg_image($atts, $content = null) {
    $cardBackSide = $atts['backside'] ?: "";
    return ufmtg_build_img("", $content, $cardBackSide);
}

function ufmtg_precache_cards($atts, $content = null) {
    extractCardsFromString($content, "[scrylink]", "[/scrylink]");
}

add_shortcode('scryimg', 'ufmtg_get_scryfall_image');
add_shortcode('scrylink', 'ufmtg_get_scryfall_link');
add_shortcode('p1p1cube', 'ufmtg_randomize_card_pack');
add_shortcode('p1p1deck', 'ufmtg_build_deck');
add_shortcode('mtgimg', 'ufmtg_get_mtg_image');
add_shortcode('mtglink', 'ufmtg_get_mtg_link');
add_shortcode('mtgprecache', 'ufmtg_precache_cards');