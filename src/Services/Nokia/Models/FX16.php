<?php

namespace PauloHortelan\OltMonitoring\Services\Nokia\Models;

use PauloHortelan\OltMonitoring\Connections\Telnet;

class FX16
{
    protected Telnet $connection;

    public function __construct(Telnet $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Returns the ONT optical power
     */
    public function ontOpticalPower(array $interfaces): array|float
    {
        $opticalPower = [];

        foreach ($interfaces as $interface) {
            $response = $this->connection->exec("show equipment ont optics $interface detail");

            if (preg_match('/rx-signal-level.*:(.*\s)/m', $response, $match)) {
                $opticalPower[] = (float) $match[1];
            } else {
                throw new \Exception('Ont optical power not found.');
            }
        }

        if (count($opticalPower) === 1) {
            return $opticalPower[0];
        }

        return $opticalPower;
    }

    /**
     * Returns the ONT optical interface
     */
    public function ontOpticalInterface(array $serials): array|string
    {
        $opticalInterface = [];

        foreach ($serials as $serial) {
            $formattedSerial = substr_replace($serial, ':', 4, 0);

            $response = $this->connection->exec("show equipment ont index sn:$formattedSerial detail");

            if (preg_match('/ont-idx.*:(.*\s)/m', $response, $match)) {
                $opticalInterface[] = trim((string) $match[1]);
            } else {
                throw new \Exception('Ont interface not found.');
            }
        }

        if (count($opticalInterface) === 1) {
            return $opticalInterface[0];
        }

        return $opticalInterface;
    }
}
