=== PrixChat - Realtime Private Chat Plugin   ===
Contributors: tanng, rilwis
Tags: chat, private chat, group chat, inbox, realtime 
Requires at least: 5.0
Tested up to: 6.0.2
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Ultimate Facebook Messenger for Marketers and Developers

== Description ==
[Giga Messenger](https://giga.ai) brings a convenience way to help you supercharge your Messenger, build your own chat bot, manage leads, subscriptions and marketing plans with no fuss. Yes! It’s not just a messenger bot plugin, it’s an ultimate marketing tool for your big idea.

### Key Features

#### Powerful & Dynamic Bot Builder
And still ease of use, Bot Builder comes with Live Preview which lets you see the result before publish. With Dynamic Data feature, you can even send dynamic content to people without writing any line of code.

#### Multipage Support (Premium)
Manage unlimited Facebook Pages at one place, each page can have their own settings or reuse other pages setting.

#### Built-in CRM
Automatically pull leads to CRM when they join the conversation, let you manage contact info from a seamless dashboard.

#### Subscription & Notification Builder (Premium)
With this feature, you can build subscription channels, send notification messages to any channel which you want, create a schedule and routine messages without headache.

#### Auto Stop
Lots of options to stop and restart the conversation between bot and leads when you want to chat with them by yourself.

#### Multi-step Conversation (Intended action)
Build a rich experience conversation tree chat bot, allow bot collect and process lead data with a real Conversational User Interface.

#### All Major Message Types
We support all major message types which no other plugin can be compared, these are: Text, Image, Audio, Video, File, Generic (Carousel), Receipt, List, URL Button, Postback Button, Share Button, Login Button, Logout Button, Location…

#### Location Support
We support sending and receiving location data, which is helpful when you want to get client location for shipping, registration, etc.

#### Persistent Menu Builder
Drag & Drop to create menu in your Messenger app in seconds. Can’t be simpler right?

#### Account Linking (Premium)
Allows people login to your system directly in Messenger, give you more power to connect with your users.

#### Beautiful Shortcodes
Just tell bot say Hi [first_name] to people and let the bot says, for example, Hi Jimmy automatically. We do support all profile shortcode, dynamic shortcodes.

#### WooCommerce Integration
Wanna show your latest product of any category for your users? We have deeply integrate with WooCommerce and you still don’t have to touch to code.

#### Light Speed
Response your customer instantly in zero time. Your Bot still run with bolt speed.

#### Simple Logger
Logger shows you all data transfer between Facebook and your server and the state of them, let you see what's happening.

#### Curated Packages
We use Angular, PHP Composer, Laravel Illuminate to power this bot. We also test the speed carefully with Blackfire.io

#### Friendly Documentation
A product can’t be cool without a good documentation. Our documentation is step by step, from basic to advanced, update daily, check it your self at [Giga AI Documentation](https://giga.ai/docs)

### Documentation
Our [Official Documentation](https://giga.ai/docs) update regularly.

== Installation ==
Important!: Make sure your PHP version is >= 5.4 and SSL Enabled.

1. Upload the plugin files to the `/wp-content/plugins/giga-messenger-bots` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the `Dashboard \ Giga AI` screen to configure the plugin
1. Follow the [Installation Guide](https://giga.ai/docs/installation) to configure Webhooks and Facebook App.

== Frequently Asked Questions ==
**Can this bot compatibility with latest Messenger Platform**
Yes, it's compatibility with Messenger Platform 2.0, and we're very active to maintain it.

**Can the bot works with multiple Facebook pages?**
Yes, you can manage many pages in one place.

**Why my bot cannot answers all users except me?**
You'll need Facebook approval for your app to let bot answer public user. See [Facebook Review Process](https://developers.facebook.com/docs/messenger-platform/app-review)


== Screenshots ==
1. Bot Builder Basic
1. Bot Builder Actions
1. Node Stream
1. Bot Builder Live Preview
1. Generic (Carousel) Message Type
1. Login Button
1. Settings Page - Basic
1. Settings Page - Thread Settings
1. Settings Page - Domain Whitelisting
1. Settings Page - Account Linking
1. Settings Page - Auto Stop
1. Facebook Messenger Bot - Welcome Screen
1. Messenger Bot - Auto Response
1. Messenger Bot - Message Template (List)
1. Messenger Bot - Message Template (Generic)
1. Messenger Bot - Sending Media
1. Messenger Bot - Persistent Menu
1. Basic CRM
1. CRM - Profile Page
1. CRM - Subscription Page
1. Subscription & Notifications Builder

== Changelog ==
#### 2.3 (April 22nd, 2017)
- New: Multiple Pages (Premium).
- New: Replace Thread Settings with Messenger Profile.
- New: Bot now can update lead based on their data via Builder.
- New: Logger module.
- New: $bot->release() method which lets you validate and forget intended actions.
- Improvement: Bot Builder responses can now draggable.
- Improvement: Search and Filter in CRM.
- Improvement: You can now use text matching and get $input variable for payload.
- Improvement: $bot->answer(); now can takes 3 arguments, the third arguments is the node attributes.
- Improvement: Support using icon in quick replies.
- Improvement: Use secured cookie as Session.
- Fix: all known CSS bugs and typo.

#### 2.2.4 (March 26, 2017)
- Improvement: Remove tooltip() method
- Fix: DB Migration doesn't works with old MySQL version

#### 2.2.3 (March 07, 2017)
- Improvement: Update gigaai/framework to the latest version.
- Improvement: More friendly message when user use PHP < 5.4.
- Fix: Notification routines doesn't works in some languages.

#### 2.2.2 (January 25, 2017)
- Add `tax` parameter support for [post-generic] shortcode.

#### 2.2.1 (January 18, 2017)
*Initial Release*