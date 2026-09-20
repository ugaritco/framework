<?php

declare(strict_types=1);

namespace Heritage\Support;

/**
 * Base Artifact representation for Ugarit's Artifact-Driven Architecture (ADA).
 */
abstract class Artifact
{
    /**
     * The unique identifier for the artifact.
     */
    public string $id;

    /**
     * The human-readable name of the artifact.
     */
    public string $name;

    /**
     * The semantic version of the artifact.
     */
    public string $version = '1.00.00';

    /**
     * Technical capabilities provided by this artifact.
     *
     * @var array<string, string>
     */
    public array $capabilities = [];

    /**
     * Service providers registered by this artifact.
     *
     * @var array<int, class-string<\Heritage\Support\ServiceProvider>>
     */
    public array $providers = [];

    /**
     * Capabilities required from other artifacts.
     *
     * @var array<int, string>
     */
    public array $requires = [];

    /**
     * Register any artifact services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap capability logic.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the artifact identifier.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the human-readable name.
     */
    public function getName(): string
    {
        return $this->name ?? $this->id;
    }

    /**
     * Get the version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get the providers.
     *
     * @return array<int, class-string<\Heritage\Support\ServiceProvider>>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get technical capabilities.
     *
     * @return array<string, string>
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }
}
