<?php

namespace Tests\Unit;

use App\Models\Connection;
use App\Models\TransportMode;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function test_the_service_is_the_line_or_the_mode_without_one(): void
    {
        $this->assertSame('line:3', $this->connection('bus', 3)->service());
        $this->assertSame('app', $this->connection('app')->service());
    }

    public function test_the_passenger_stays_on_board_only_on_the_same_line_or_vehicle(): void
    {
        $this->assertTrue($this->connection('bus', 3)->continuesOnBoard($this->connection('bus', 3)));
        $this->assertTrue($this->connection('app')->continuesOnBoard($this->connection('app')));
        $this->assertFalse($this->connection('bus', 3)->continuesOnBoard($this->connection('bus', 4)));
        $this->assertFalse($this->connection('metro', 3)->continuesOnBoard($this->connection('walk')));
        $this->assertFalse($this->connection('walk')->continuesOnBoard($this->connection('walk')));
    }

    private function connection(string $mode, ?int $line = null): Connection
    {
        return (new Connection)->forceFill(['line_id' => $line])->setRelation('mode', (new TransportMode)->forceFill(['code' => $mode]));
    }
}
