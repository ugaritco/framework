<?php

declare(strict_types=1);

namespace Heritage\Actions;

use Heritage\Enums\Actions\ActionType;
use InvalidArgumentException;

/**
 * Class Action
 *
 * Represents an actionable instruction returned by a domain Result to the UI or frontend client.
 *
 * Supported Action Types:
 * 1. REDIRECT: Provides a target URL and button label to guide the user to another page or route.
 * 2. EVENT: Dispatches a named frontend event to trigger client-side reactions (e.g. close modal, reload table).
 * 3. CALLBACK: Server-side executable callable for post-operation workflows.
 */
class Action
{
    /**
     * Initialize the Action instance and validate its payload structure based on the action type.
     *
     * @param  ActionType  $type  The type of action (REDIRECT, EVENT, CALLBACK).
     * @param  array<string, mixed>  $payload  Associated action payload.
     */
    public function __construct(
        public ActionType $type,
        public array $payload = []
    ) {
        // Validate payload attributes immediately upon instantiation
        $this->validatePayload();
    }

    /**
     * Validate the payload structure according to the action type.
     *
     * @return void
     */
    private function validatePayload(): void
    {
        // Match action type and delegate to specialized validation method
        match ($this->type) {
            ActionType::REDIRECT => $this->validateRedirect(),
            ActionType::EVENT => $this->validateEvent(),
            ActionType::CALLBACK => $this->validateCallback(),
            default => null,
        };
    }

    /**
     * Validate REDIRECT action payload requirements.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function validateRedirect(): void
    {
        // Ensure string URL exists in payload
        if (! isset($this->payload['url']) || ! is_string($this->payload['url'])) {
            throw new InvalidArgumentException("REDIRECT action requires a 'url' string in payload");
        }

        // Ensure string button label exists in payload
        if (! isset($this->payload['label']) || ! is_string($this->payload['label'])) {
            throw new InvalidArgumentException("REDIRECT action requires a 'label' string in payload");
        }
    }

    /**
     * Validate EVENT action payload requirements.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function validateEvent(): void
    {
        // Ensure event name string exists in payload
        if (! isset($this->payload['event']) || ! is_string($this->payload['event'])) {
            throw new InvalidArgumentException("EVENT action requires an 'event' string in payload");
        }
    }

    /**
     * Validate CALLBACK action payload requirements.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function validateCallback(): void
    {
        // Ensure callback is a valid PHP callable
        if (! isset($this->payload['callback']) || ! is_callable($this->payload['callback'])) {
            throw new InvalidArgumentException("CALLBACK action requires a 'callback' callable in payload");
        }
    }

    /**
     * Convert the action instance to an associative array format.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            // String representation of action type
            'type' => $this->type->value,
            // Associated payload data
            'payload' => $this->payload,
        ];
    }

    /**
     * Helper factory to create a REDIRECT action.
     *
     * @param  string  $url  The target URL.
     * @param  string  $label  The interactive button label.
     * @return self
     */
    public static function redirect(string $url, string $label): self
    {
        return new self(ActionType::REDIRECT, ['url' => $url, 'label' => $label]);
    }

    /**
     * Helper factory to create an EVENT action.
     *
     * @param  string  $event  The event name.
     * @param  array<string, mixed>  $params  Event parameters.
     * @return self
     */
    public static function event(string $event, array $params = []): self
    {
        return new self(ActionType::EVENT, ['event' => $event, 'params' => $params]);
    }

    /**
     * Helper factory to create a CALLBACK action.
     *
     * @param  callable  $callback  The callable handler.
     * @return self
     */
    public static function callback(callable $callback): self
    {
        return new self(ActionType::CALLBACK, ['callback' => $callback]);
    }
}
