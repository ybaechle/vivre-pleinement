<?php

it('rejects uploads hiding an executable extension', function () {
    expect(config('media-library.disallowed_extensions'))->toContain('php');
});
