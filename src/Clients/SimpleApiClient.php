<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Clients;

class SimpleApiClient implements ApiClient
{
    use SendsHttpRequests;

    private const BASE_API = 'https://slack.com/api/';

    /**
     * The list of all Slack API operations that support being called with the application/json Content-Type.
     *
     * Can get updated list with this command:
     *
     *     curl -s https://raw.githubusercontent.com/slackapi/slack-api-specs/master/web-api/slack_web_openapi_v2.json \
     *         | jq -r '.paths | with_entries(.value = ({api: .key, consumes: .value[].consumes})) | .[] \
     *         | select(.consumes[] | contains("application/json")) | .api | ltrimstr("/") | "        \"\(.)\","'
     */
    private const SUPPORTS_JSON = [
        'admin.apps.approve',
        'admin.apps.restrict',
        'admin.conversations.archive',
        'admin.conversations.convertToPrivate',
        'admin.conversations.create',
        'admin.conversations.delete',
        'admin.conversations.disconnectShared',
        'admin.conversations.getConversationPrefs',
        'admin.conversations.getTeams',
        'admin.conversations.invite',
        'admin.conversations.rename',
        'admin.conversations.search',
        'admin.conversations.setConversationPrefs',
        'admin.conversations.setTeams',
        'admin.conversations.unarchive',
        'admin.inviteRequests.approve',
        'admin.inviteRequests.approved.list',
        'admin.inviteRequests.denied.list',
        'admin.inviteRequests.deny',
        'admin.inviteRequests.list',
        'admin.teams.create',
        'admin.teams.list',
        'admin.teams.settings.info',
        'admin.teams.settings.setDescription',
        'admin.teams.settings.setDiscoverability',
        'admin.teams.settings.setName',
        'admin.usergroups.addChannels',
        'admin.usergroups.addTeams',
        'admin.usergroups.listChannels',
        'admin.usergroups.removeChannels',
        'admin.users.assign',
        'admin.users.invite',
        'admin.users.list',
        'admin.users.remove',
        'admin.users.session.invalidate',
        'admin.users.session.reset',
        'admin.users.setAdmin',
        'admin.users.setExpiration',
        'admin.users.setOwner',
        'admin.users.setRegular',
        'api.test',
        'apps.event.authorizations.list',
        'auth.test',
        'calls.add',
        'calls.end',
        'calls.info',
        'calls.participants.add',
        'calls.participants.remove',
        'calls.update',
        'chat.delete',
        'chat.deleteScheduledMessage',
        'chat.meMessage',
        'chat.postEphemeral',
        'chat.postMessage',
        'chat.scheduleMessage',
        'chat.scheduledMessages.list',
        'chat.unfurl',
        'chat.update',
        'conversations.archive',
        'conversations.close',
        'conversations.create',
        'conversations.invite',
        'conversations.join',
        'conversations.kick',
        'conversations.leave',
        'conversations.mark',
        'conversations.open',
        'conversations.rename',
        'conversations.setPurpose',
        'conversations.setTopic',
        'conversations.unarchive',
        'dialog.open',
        'dnd.endDnd',
        'dnd.endSnooze',
        'files.comments.delete',
        'files.delete',
        'files.revokePublicURL',
        'files.sharedPublicURL',
        'pins.add',
        'pins.remove',
        'reactions.add',
        'reactions.remove',
        'reminders.add',
        'reminders.complete',
        'reminders.delete',
        'stars.add',
        'stars.remove',
        'usergroups.create',
        'usergroups.disable',
        'usergroups.enable',
        'usergroups.update',
        'usergroups.users.update',
        'users.profile.set',
        'users.setActive',
        'users.setPresence',
        'views.open',
        'views.publish',
        'views.push',
        'views.update',
        'workflows.stepCompleted',
        'workflows.stepFailed',
        'workflows.updateStep',
    ];

    private ?string $apiToken;

    public function __construct(?string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function call(string $api, array $params): array
    {
        if (!isset($params['token']) && isset($this->apiToken)) {
            $params['token'] = $this->apiToken;
        }

        $url = self::BASE_API . $api;

        return in_array($api, self::SUPPORTS_JSON, true)
            ? $this->sendJsonRequest('POST', $url, $params)
            : $this->sendFormRequest('POST', $url, $params);
    }
}
