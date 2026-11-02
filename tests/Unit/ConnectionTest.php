<?php

namespace Tests\Unit;

use App\Models\Connection;
use App\Models\TransportMode;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function test_the_service_is_the_line_the_hired_ride_or_the_mode(): void
    {
        $this->assertSame('line:3', $this->connection('bus', 3)->service());
        $this->assertSame('app:21', $this->connection('app', id: 21)->service());
        $this->assertSame('taxi:22', $this->connection('taxi', id: 22)->service());
        $this->assertSame('bike', $this->connection('bike', id: 23)->service());
    }

    public function test_the_passenger_stays_on_board_only_on_the_same_line_or_vehicle(): void
    {
        $this->assertTrue($this->connection('bus', 3)->continuesOnBoard($this->connection('bus', 3)));
        $this->assertTrue($this->connection('bike', id: 23)->continuesOnBoard($this->connection('bike', id: 24)));
        $this->assertFalse($this->connection('app', id: 21)->continuesOnBoard($this->connection('app', id: 25)));
        $this->assertFalse($this->connection('taxi', id: 22)->continuesOnBoard($this->connection('taxi', id: 26)));
        $this->assertFalse($this->connection('bus', 3)->continuesOnBoard($this->connection('bus', 4)));
        $this->assertFalse($this->connection('metro', 3)->continuesOnBoard($this->connection('walk')));
        $this->assertFalse($this->connection('walk')->continuesOnBoard($this->connection('walk')));
    }

    private function connection(string $mode, ?int $line = null, ?int $id = null): Connection
    {
        return (new Connection)->forceFill(['id' => $id, 'line_id' => $line])->setRelation('mode', (new TransportMode)->forceFill(['code' => $mode]));
    }
}
