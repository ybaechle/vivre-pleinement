<?php

use App\Support\LikeSearch;

it('escapes the LIKE wildcards and the escape character itself', function () {
    expect(LikeSearch::wrap('50%_a\\'))->toBe('%50\\%\\_a\\\\%');
});
