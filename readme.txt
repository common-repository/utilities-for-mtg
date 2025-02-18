﻿=== Utilities for MTG ===
Contributors: Yunra, dbudbu
Tags: scryfall, mtg, magic, tcg
Donate link: https://www.patreon.com/yunra
Requires at least: 4.9.8
Tested up to: 5.5
Stable tag: 1.4.1
Requires PHP: 5.5.4

Get links and pictures of cards just by typing the cards name.

== Description ==
This plugin is made to spice up your pages with cards from Magic The Gathering.
When you talk about a card in a blogpost it is always nice to display it, now you can easily do that without having to find pictures and create links etc, just type the name in one of the available tags!

It is originally created for thepaupercube.com

== Screenshots ==
1. Displayed cards
2. Hovering with the cursor over one card will zoom it

== Installation ==
Just download and activate the plugin and start using the tags in your pages. No additional configuration or setup needed.

== Usage ==
[scryimg set="azn"]cardname[/scryimg] results in a picture of the card (set is not required)

[scrylink set="azn"]cardname[/scrylink] results in a link to the card on scryfall with an image of the card on hover

[p1p1cube column="2"]http://example.com/mycube.csv[/p1p1cube] will generate 15 cards from the X (where X in this example is 2) column of a csv file (Column B in a google spreadsheet f.ex). In order for this to work, the link between the tags need to be a direct link to a published CSV file.

[p1p1deck]gishath:ghost quarter:grunn, the lonely king:serra angel:cancel[/p1p1deck] - Make a list of cardimages by typing in the names of cards separated by :

[mtgimg backside="https://url-to-an-image.com/image.png"]https://url-to-an-image.com/image.png[/mtgimg] - requires an image url between the tags. Can take a second image and use as the backside on f.ex a flipcards. 

[mtglink url="https://url-to.link" name="Displayed name"]https://url-to-an-image.com/image.png[/mtglink] - requires an image url between the tags, a name as parameter and a url as parameter. Can take a second image and use as the backside on f.ex flipcards.

[mtgprecache]card|card:set|card|card[/mtgprecache] - Used to pre-cache cards to speed up loading of page. Requires perfect spelling of cardnames.

== Changelog ==

= 1.4.1 =
* Optimized delay of scryfall requests

= 1.4.0 =
* Updated to display new double faced cards

= 1.3.0 =
* No changes to how shortcodes work, just code cleanup.

= 1.1.1 = 
* Fixed a bug where the precache did not accept setcode

= 1.1.0 = 
* New shortcode [mtgprecache]card|card|card|card[/mtgprecache] - Used to pre-cache cards to speed up loading of page. Requires perfect spelling of cardnames.

= 1.0.0 = 
* Fixes for release to Wordpress Plugins section

= 0.9.1 =
* Fixed display of double faced cards, they are now displayed as a single image and when clicked it will show the backside of the card.
* Fixed a bug where the name matcher was too forgiving and let everything with similair names through.

== Additional Info ==

* This plugin is using the Scryfall API (http://www.scryfall.com) for card data and images. Data is not modified and can be found and viewed in full on Scryfall.com. Scryfall TOS: https://scryfall.com/docs/terms

== Credit ==
* Thanks to Adam at thepaupercube.com for using, testing and providing feedback
