<header>
  <h1 align="center">Slack App Framework for PHP</h1>
  <p align="center">By Jeremy Lindblom (<a href="https://twitter.com/jeremeamia">@jeremeamia</a>)</p>
</header>

<p align="center">
  <img src="https://repository-images.githubusercontent.com/337916048/1ab9fb00-87f7-11eb-9eda-f25ffb705e87" alt="Slack PHP logo written in PHP's font" width="65%">
</p>

<p align="center">
  <a href="http://php.net/">
    <img src="https://img.shields.io/badge/code-php%207.4-8892bf.svg" alt="Coded in PHP 7.4">
  </a>
  <a href="https://packagist.org/packages/slack-php/slack-block-kit">
    <img src="https://img.shields.io/packagist/v/slack-php/slack-app-framework.svg" alt="Packagist Version">
  </a>
  <a href="https://actions-badge.atrox.dev/slack-php/slack-php-app-framework/goto?ref=main">
    <img alt="Build Status" src="https://img.shields.io/endpoint.svg?url=https%3A%2F%2Factions-badge.atrox.dev%2Fslack-php%2Fslack-php-app-framework%2Fbadge%3Fref%3Dmain&style=flat" />
  </a>
</p>

---

# Introduction

A PHP framework for building Slack apps. It takes inspiration from Slack's Bolt frameworks.

If you are new to Slack app development, you will want to learn about it on
[Slack's website](https://api.slack.com/start). This library is only useful if you already understand the basics of
building Slack applications.

## Installation

- Requires PHP 7.4+
- Use Composer to install: `composer require slack-php/slack-app-framework`

## General Usage

### Quick Warning

This library has been heavily dogfooded, but the project is severely lacking in test coverage and documentation, so
use it at your own risk, as the MIT license advises.

- Contributions welcome (especially for documentation and tests).
- For questions, feedback, suggestions, etc., use [Discussions][].
- For issues or concerns, use [Issues][].

### Development Patterns

When creating an app, you can configure your app from the Slack website. The framework is designed to recieve requests
from all of your app's interaction points, so you should configure all of the URLs (e.g., in **Slash Commands**,
**Interactivity & Shortcuts** (don't forget the _Select Menus_ section), and **Event Subscriptions**) to point to the
root URL of your deployed app code.

When developing the app code, you declare one or more `Listener`s using the `App`'s routing methods that correspond to
the different types of app interaction. `Listener`s can be declared as closures, or as objects and class names of type
`SlackPhp\Framework\Listener`. A `Listener` receives a `Context` object, which contains the payload data provided by
Slack to the app and provides methods for all the actions you can take to interact with or communicate back to Slack.

## Quick Example

This small app responds to the `/cool` slash command.

> Assumptions:
>
> - You have required the Composer autoloader to enable autoloading of the framework files.
> - You have set `SLACK_SIGNING_KEY` in the environment (e.g., `putenv("SLACK_SIGNING_KEY=foo");`)

```php
<?php

use SlackPhp\Framework\App;
use SlackPhp\Framework\Context;

App::new()
    ->command('cool', function (Context $ctx) {
        $ctx->ack(':thumbsup: That is so cool!');
    })
    ->run();
```

## Example Application

The "Hello World" app says hello to you, by utilizing every type of app interactions, including: slash commands, block
actions, block suggestions (i.e., options for menus), shortcuts (both global and message level), modals, events, and
the app home page.

<details>
<summary>"Hello World" app code</summary>

> Assumptions:
>
> - You have required the Composer autoloader to enable autoloading of the framework files.
> - You have set `SLACK_SIGNING_KEY` in the environment (e.g., `putenv("SLACK_SIGNING_KEY=foo");`)
> - You have set `SLACK_BOT_TOKEN` in the environment (e.g., `putenv("SLACK_BOT_TOKEN=bar");`)

```php
<?php

declare(strict_types=1);

use SlackPhp\BlockKit\Surfaces\{Message, Modal};
use SlackPhp\Framework\{App, Context, Route};

// Helper for creating a modal with the "hello-form" for choosing a greeting.
$createModal = function (): Modal {
    return Modal::new()
        ->title('Choose a Greeting')
        ->submit('Submit')
        ->callbackId('hello-form')
        ->notifyOnClose(true)
        ->tap(function (Modal $modal) {
            $modal->newInput('greeting-block')
                ->label('Which Greeting?')
                ->newSelectMenu('greeting')
                ->forExternalOptions()
                ->placeholder('Choose a greeting...');
        });
};

App::new()
    // Handles the `/hello` slash command.
    ->command('hello', function (Context $ctx) {
        $ctx->ack(Message::new()->tap(function (Message $msg) {
            $msg->newSection()
                ->mrkdwnText(':wave: Hello world!')
                ->newButtonAccessory('open-form')
                ->text('Choose a Greeting');
        }));
    })
    // Handles the "open-form" button click.
    ->blockAction('open-form', function (Context $ctx) use ($createModal) {
        $ctx->modals()->open($createModal());
    })
    // Handles when the "greeting" select menu needs its options.
    ->blockSuggestion('greeting', function (Context $ctx) {
        $ctx->options(['Hello', 'Howdy', 'Good Morning', 'Hey']);
    })
    // Handles when the "hello-form" modal is submitted.
    ->viewSubmission('hello-form', function (Context $ctx) {
        $state = $ctx->payload()->getState();
        $greeting = $state->get('greeting-block.greeting.selected_option.value');
        $ctx->view()->update(":wave: {$greeting} world!");
    })
    // Handles when the "hello-form" modal is closed without submitting.
    ->viewClosed('hello-form', function (Context $ctx) {
        $ctx->logger()->notice('User closed hello-form modal early.');
    })
    // Handles when the "hello-global" global shortcut is triggered from the lightning menu.
    ->globalShortcut('hello-global', function (Context $ctx) use ($createModal) {
        $ctx->modals()->open($createModal());
    })
    // Handles when the "hello-message" message shortcut is triggered from a message context menu.
    ->messageShortcut('hello-message', function (Context $ctx) {
        $user = $ctx->fmt()->user($ctx->payload()->get('message.user'));
        $ctx->say(":wave: Hello {$user}!", null, $ctx->payload()->get('message.ts'));
    })
    // Handles when the Hello World app "home" is accessed.
    ->event('app_home_opened', function (Context $ctx) {
        $user = $ctx->fmt()->user($ctx->payload()->get('event.user'));
        $ctx->home(":wave: Hello {$user}!");
    })
    // Handles when any public message contains the word "hello".
    ->event('message', Route::filter(
        ['event.channel_type' => 'channel', 'event.text' => 'regex:/^.*hello.*$/i'],
        function (Context $ctx) {
            $user = $ctx->fmt()->user($ctx->payload()->get('event.user'));
            $ctx->say(":wave: Hello {$user}!");
        })
    )
    // Run that app to process the incoming Slack request.
    ->run();
```

</details>

### Object-Oriented Version

You can alternatively create your App and Listeners as a set of classes. I recommend this approach if you have more than
a few listeners or if your listeners are complicated. Here is an example of how the "Hello World" app would look when
developed in this way.

<details>
<summary>"Hello World" app code</summary>

`App.php` 
```php
<?php

declare(strict_types=1);

namespace MyApp;

use SlackPhp\Framework\{BaseApp, Route, Router};
use MyApp\Listeners;

class MyCoolApp extends BaseApp
{
    protected function prepareRouter(Router $router): void
    {
        $router->command('hello', Listeners\HelloCommand::class)
            ->blockAction('open-form', Listeners\OpenFormButtonClick::class)
            ->blockSuggestion('greeting', Listeners\GreetingOptions::class)
            ->viewSubmission('hello-form', Listeners\FormSubmission::class)
            ->viewClosed('hello-form', Listeners\FormClosed::class)
            ->globalShortcut('hello-global', Listeners\HelloGlobalShortcut::class)
            ->messageShortcut('hello-message', Listeners\HelloMessageShortcut::class)
            ->event('app_home_opened', Listeners\AppHome::class)
            ->event('message', Route::filter(
                ['event.channel_type' => 'channel', 'event.text' => 'regex:/^.*hello.*$/i'],
                Listeners\HelloMessage::class
            ));
    }
}
```

`index.php`

> Assumptions:
>
> - You have required the Composer autoloader to enable autoloading of the framework files.
> - You have configured composer.json so that your `MyApp` namespaced code is autoloaded.
> - You have set `SLACK_SIGNING_KEY` in the environment (e.g., `putenv("SLACK_SIGNING_KEY=foo");`)
> - You have set `SLACK_BOT_TOKEN` in the environment (e.g., `putenv("SLACK_BOT_TOKEN=bar");`)

```php
<?php

use MyApp\MyCoolApp;

$app = new MyCoolApp();
$app->run();
```

</details>

## Handling Requests with the `Context` Object

The `Context` object is the main point of interaction between your app and Slack. Here are all the things you can do
with the `Context`:

```
// To respond (ack) to incoming Slack request:
$ctx->ack(Message|array|string|null)  // Responds to request with 200 (and optional message)
$ctx->options(OptionList|array|null)  // Responds to request with an options list
$ctx->view(): View
  ->clear()                           // Responds to modal submission by clearing modal stack
  ->close()                           // Responds to modal submission by clearing current modal
  ->errors(array)                     // Responds to modal submission by providing form errors
  ->push(Modal|array|string)          // Responds to modal submission by pushing new modal to stack
  ->update(Modal|array|string)        // Responds to modal submission by updating current modal

// To call Slack APIs (to send messages, open/update modals, etc.) after the ack:
$ctx->respond(Message|array|string)   // Responds to message. Uses payload.response_url
$ctx->say(Message|array|string)       // Responds in channel. Uses API and payload.channel.id
$ctx->modals(): Modals
  ->open(Modal|array|string)          // Opens a modal. Uses API and payload.trigger_id
  ->push(Modal|array|string)          // Pushes a new modal. Uses API and payload.trigger_id
  ->update(Modal|array|string)        // Updates a modal. Uses API and payload.view.id
$ctx->home(AppHome|array|string)      // Modifies App Home for user. Uses API and payload.user.id
$ctx->api(string $api, array $params) // Use Slack API client for arbitrary API operations

// Access payload or other contextual data:
$ctx->payload(): Payload              // Returns the payload of the incoming request from Slack
$ctx->getAppId(): ?string             // Gets the app ID, if it's known
$ctx->get(string): mixed              // Gets a value from the context
$ctx->set(string, mixed)              // Sets a value in the context
$ctx->isAcknowledged(): bool          // Returns true if ack has been sent
$ctx->isDeferred(): bool              // Returns true if additional processing will happen after the ack

// Access additional helpers:
$ctx->blocks(): Blocks                // Returns a helper for creating Block Kit surfaces
$ctx->fmt(): Formatter                // Returns the "mrkdwn" formatting helper for Block Kit text
$ctx->logger(): LoggerInterface       // Returns an instance of the configured PSR-3 logger
$ctx->container(): ContainerInterface // Returns an instance of the configured PSR-11 container
```

## High Level Design

![UML diagram of the framework](https://yuml.me/68717414.png)

<details>
<summary>YUML Source</summary>
<pre>
[AppServer]<>-runs>[App]
[AppServer]creates->[Context]
[App]<>->[AppConfig]
[App]<>->[Router]
[Router]-^[Listener]
[Router]<>1-*>[Listener]
[Listener]handles->[Context]
[Context]<>->[Payload]
[Context]<>->[AppConfig]
[Context]<>->[_Clients_;RespondClient;ApiClient]
[Context]<>->[_Helpers_;BlockKit;Modals;View]
[Context]<>->[_Metadata_]
[AppConfig]<>->[Logger]
[AppConfig]<>->[Container]
[AppConfig]<>->[_Credentials_]
</pre>
</details>

## Socket Mode

[Socket mode][] support is provided by a separate package. See [slack-php/slack-php-socket-mode][].

## Not Implemented

The following features are known to be missing:

- OAuth flow for handling installations to a different workspace.
    - Though there are some class in the `SlackPhp\Framework\Auth` namespace if you need to roll your own right now.

## Standards Used

- PSR-1, PSR-12: Coding Style
- PSR-3: Logger Interface
- PSR-4: Autoloading
- PSR-7, PSR-15, PSR-17: HTTP
- PSR-11: Container Interface

[Discussions]: https://github.com/slack-php/slack-php-app-framework/discussions
[Issues]: https://github.com/slack-php/slack-php-app-framework/issues
[Pull Request]: https://github.com/slack-php/slack-php-app-framework/pulls
[Socket Mode]: https://api.slack.com/apis/connections/socket
[slack-php/slack-php-socket-mode]: https://github.com/slack-php/slack-php-socket-mode
