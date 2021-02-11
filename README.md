# Slack App Framework for PHP

A small, PHP framework for building Slack Apps. Takes inspiration from Slack's Bolt frameworks.

## Example

```php
<?php

declare(strict_types=1);

use Jeremeamia\Slack\Apps\App;
use Jeremeamia\Slack\Apps\Context;
use Jeremeamia\Slack\BlockKit\Partials\OptionList;
use Jeremeamia\Slack\BlockKit\Surfaces\Message;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

// Bootstrap Slack App
$app = App::new()
    ->command('slack-test', function (Context $ctx) {
        $ctx->respond(':thumbsup: *Success*');
    })
    ->shortcut('shortcut_test', function (Context $ctx) {
        $modal = Modal::new()
            ->title('Hello')
            ->text(':wave: Hello from a *Global Shortcut*.');
        $ctx->modal($modal);
    })
    ->messageShortcut('message_action_test', function (Context $ctx) {
        $ctx->respond(':wave: Hello from a *Message Action*.');
    })
    ->blockSuggestion('custom_options', function (Context $ctx) {
        $ctx->options([
            'Option 1' => 'foo',
            'Option 2' => 'bar',
            'Option 3' => 'baz',
        ]);
    })
    ->blockAction('test-button', function (Context $ctx) {
        $action = $ctx->payload()->asBlockActions()->getActions()[0];
        $msg = $ctx->blocks()->newMessage();
        $msg->newTwoColumnTable()
            ->caption('*Action*')
            ->row('`type`', $action->getType())
            ->row('`block_id`', $action->getBlockId())
            ->row('`action_id`', $action->getActionId())
            ->row('`value`', $action->getValue());
        $ctx->respond($msg);
    })
    ->event('app_home_opened', function (Context $ctx) {
        $event = $ctx->payload()->asEventCallback()->getEvent();
        $user = $ctx->fmt()->user($event->get('user'));
        $home = $ctx->blocks()->appHome()->text(":wave: Hello, {$user}! This is your *App Home*.");
        $ctx->home($home);
    })
    ->run();
```

## Handling Requests with the `Context` Object

```
$context

  // To respond (ack) to incoming Slack request:
    ->ack(Message|string|null)       // Responds to request with 200 (opt. and message) and defers
    ->done(Message|string|null)      // Responds to request with 200 (opt. and message) and does not defer
    ->options(OptionList|array|null) // Responds to request with an options list
    ->view()
      ->clear()                      // Responds to modal submission by clearing modal stack
      ->close()                      // Responds to modal submission by clearing current modal
      ->errors(array)                // Responds to modal submission by providing form errors
      ->push(Modal)                  // Responds to modal submission by pushing new modal to stack
      ->update(Modal)                // Responds to modal submission by updating current modal

  // To call Slack APIs (to send messages, open/update modals, etc.) after the ack:
    ->respond(Message|string|array)  // Responds to message. Uses payload.response_url
    ->say(Message|string|array)      // Responds in channel. Uses API and payload.channel.id
    ->modal(Modal)                   // Opens modal. Uses API and payload.trigger_id
    ->home(AppHome)                  // Modifies App Home for user. Uses API and payload.user.id
    ->api()->{$method}(...$args)     // Use Slack API client for arbitrary API operations

  // Extra helpers
    ->payload()                      // Returns the payload of the incoming request from Slack
    ->blocks()                       // Returns an object that provides ability to create BlockKit objects
    ->fmt()                          // Returns the block kit formatter
    ->logger()                       // Returns an instance of a PSR-3 logger
    ->get(string)                    // Returns a value from the context
    ->set(string, mixed)             // Sets a value in the context
```
