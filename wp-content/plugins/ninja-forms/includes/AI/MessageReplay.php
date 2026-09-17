<?php

namespace NinjaForms\Includes\AI;

/**
 * Makes model messages safe to replay in a provider-agnostic agent loop.
 */
class MessageReplay
{
    /**
     * Remove provider reasoning blocks before replaying a model message.
     *
     * Anthropic and Google require opaque signatures when reasoning/thinking
     * blocks are sent back. Provider adapters have not consistently preserved
     * those signatures. Visible text, function calls, and their order are
     * retained, so the next tool turn has all portable conversation state.
     *
     * @param object $message Model message with getParts().
     * @return object A new message of the same class without thought parts.
     */
    public static function withoutThoughts(object $message): object
    {
        $parts = array();
        foreach ($message->getParts() as $part) {
            $channel = null;
            try {
                $channel = $part->getChannel();
            } catch (\Throwable $e) {
                // Older clients have no channels; every part is portable.
            }

            if (null !== $channel && 'thought' === (string) $channel) {
                continue;
            }

            $parts[] = $part;
        }

        $message_class = get_class($message);

        try {
            // ModelMessage and compatible provider DTOs accept parts only.
            return new $message_class($parts);
        } catch (\TypeError $error) {
            unset($error);
            // Text generation results on WP 7.0 return the base Message DTO,
            // whose constructor also requires the original role.
            return new $message_class($message->getRole(), $parts);
        }
    }
}
