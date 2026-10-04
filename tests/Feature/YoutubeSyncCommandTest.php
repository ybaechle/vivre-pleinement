<?php

use Illuminate\Support\Facades\Exceptions;

it('reports a failed synchronisation instead of only printing it', function () {
    Exceptions::fake();
    config(['services.youtube.api_key' => null, 'services.youtube.channel_id' => null]);

    $this->artisan('youtube:sync')->assertFailed();

    Exceptions::assertReported(RuntimeException::class);
});
