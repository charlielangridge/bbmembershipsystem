<?php

it('reports application liveness without exposing configuration', function () {
    $response = $this->get('/up');

    $response->assertOk()
        ->assertDontSee('APP_KEY')
        ->assertDontSee('DB_PASSWORD')
        ->assertDontSee('FLARE_KEY');

    foreach ([config('app.key'), config('database.connections.mysql.password')] as $secret) {
        if (is_string($secret) && strlen($secret) >= 12) {
            $response->assertDontSee($secret);
        }
    }
});
