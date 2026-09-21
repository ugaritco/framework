<?php

declare(strict_types=1);

namespace Heritage\Actions\Feedback;

use Heritage\Actions\Action;
use Heritage\Enums\Actions\Feedback\Variant;

/**
 * Class Toast
 *
 * Immutable value object representing a structured user-facing toast feedback notification.
 *
 * Features:
 * 1. Holds feedback variant (SUCCESS, ERROR, WARNING, INFO), title, message, and interactive actions.
 * 2. Provides flash() method to place the toast into session flash data under key 'toast' for the next request.
 * 3. Provides toArray() method for serialization to JSON consumed by UI components (Vue / React / Svelte).
 */
final readonly class Toast
{
    /**
     * Initialize the immutable Toast notification instance.
     *
     * @param  Variant  $variant  Feedback visual variant (SUCCESS, ERROR, WARNING, INFO).
     * @param  string  $title  Notification title.
     * @param  string  $message  Detailed notification message.
     * @param  Action[]  $actions  Interactive actions attached to the notification.
     */
    public function __construct(
        public Variant $variant,
        public string $title,
        public string $message,
        public array $actions = []
    ) {
    }

    /**
     * Fluent static factory method to create a Toast instance.
     *
     * @param  Variant  $variant  Feedback visual variant.
     * @param  string  $title  Notification title.
     * @param  string  $message  Detailed message.
     * @param  Action[]  $actions  Attached interactive actions.
     * @return self
     */
    public static function make(Variant $variant, string $title, string $message, array $actions = []): self
    {
        return new self($variant, $title, $message, $actions);
    }

    /**
     * Flash the toast into the HTTP session so it will be displayed on the next request.
     *
     * @return void
     */
    public function flash(): void
    {
        // Verify session function availability
        if (function_exists('session')) {
            // Flash serialized toast array under 'toast' session key
            session()->flash('toast', $this->toArray());
        }
    }

    /**
     * Convert the toast object and its interactive actions into an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            // String value of visual variant (e.g. 'success', 'error')
            'variant' => $this->variant->value,

            // Notification title
            'title' => $this->title,

            // Notification message
            'message' => $this->message,

            // Map each Action instance to its array representation
            'actions' => array_map(
                fn (Action $action) => $action->toArray(),
                $this->actions
            ),
        ];
    }
}
