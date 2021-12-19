<?php

test('packages limit option called', function () {
    $this->artisan('packages',  ['--limit'=> 10]);
    $this->assertCommandCalled('packages', ['--limit' => 10]);
});

test('packages to not create a new directory', function() {
    $this->artisan('packages',  ['--output'=> true])
    ->expectsQuestion('Enter directory path where do you want to save your file', '~/Desktop/Test')
    ->expectsConfirmation('This directory does not exist, do you want to create this directory?', 'no')
    ->assertExitCode(1);
});


