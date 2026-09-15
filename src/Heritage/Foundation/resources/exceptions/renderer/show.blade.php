<x-ugarit-exceptions-renderer::layout>
    <x-ugarit-exceptions-renderer::section-container class="px-6 py-0 sm:py-0">
        <x-ugarit-exceptions-renderer::topbar :title="$exception->title()" :markdown="$exceptionAsMarkdown" />
    </x-ugarit-exceptions-renderer::section-container>

    <x-ugarit-exceptions-renderer::separator />

    <x-ugarit-exceptions-renderer::section-container class="flex flex-col gap-8 py-0 sm:py-0">
        <x-ugarit-exceptions-renderer::header :$exception />
    </x-ugarit-exceptions-renderer::section-container>

    <x-ugarit-exceptions-renderer::separator class="-mt-5 -z-10" />

    <x-ugarit-exceptions-renderer::section-container class="flex flex-col gap-8 pt-14">
        <x-ugarit-exceptions-renderer::trace :$exception />

        @if ($exception->previousExceptions()->isNotEmpty())
            <x-ugarit-exceptions-renderer::previous-exceptions :$exception />
        @endif

        <x-ugarit-exceptions-renderer::query :queries="$exception->applicationQueries()" />
    </x-ugarit-exceptions-renderer::section-container>

    <x-ugarit-exceptions-renderer::separator />

    <x-ugarit-exceptions-renderer::section-container class="flex flex-col gap-12">
        <x-ugarit-exceptions-renderer::request-header :headers="$exception->requestHeaders()" />

        <x-ugarit-exceptions-renderer::request-body :body="$exception->requestBody()" />

        <x-ugarit-exceptions-renderer::routing :routing="$exception->applicationRouteContext()" />

        <x-ugarit-exceptions-renderer::routing-parameter :routeParameters="$exception->applicationRouteParametersContext()" />
    </x-ugarit-exceptions-renderer::section-container>

    <x-ugarit-exceptions-renderer::separator />

    @if (! app()->runningUnitTests() && ! app()->runningInConsole())
        <x-ugarit-exceptions-renderer::section-container class="pb-0 sm:pb-0">
            <x-ugarit-exceptions-renderer::ugarit-ascii-spotlight />
        </x-ugarit-exceptions-renderer::section-container>
    @endif
</x-ugarit-exceptions-renderer::layout>
