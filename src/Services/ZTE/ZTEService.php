<?php

namespace PauloHortelan\OltMonitoring\Services\ZTE;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use PauloHortelan\OltMonitoring\Connections\Telnet;
use PauloHortelan\OltMonitoring\Models\Olt;
use PauloHortelan\OltMonitoring\Models\Ont;
use PauloHortelan\OltMonitoring\Services\Concerns\Validations;
use PauloHortelan\OltMonitoring\Services\ZTE\Models\C300;
use PauloHortelan\OltMonitoring\Services\ZTE\Models\C600;

class ZTEService
{
    use Validations;

    private Telnet $connection;

    private string $model = 'C300';

    private array $interfaces = [];

    private array $serials = [];

    public function connect(Olt $olt, int $timeout = 3, int $streamTimeout = 3): mixed
    {
        if (! $this->oltValid($olt)) {
            throw new Exception('OLT brand does not match the service.');
        }

        $this->model = $olt->model;
        $this->connection = Telnet::getInstance($olt->host, 23, $timeout, $streamTimeout, $olt->username, $olt->password, 'ZTE-'.$this->model);
        $this->connection->stripPromptFromBuffer(true);
        $this->connection->exec('terminal length 0');

        return $this;
    }

    public function disconnect(): void
    {
        if (empty($this->connection)) {
            throw new Exception('No connection established.');
        }

        $this->connection->destroy();
    }

    public function ont(Ont $ont): mixed
    {
        $this->connect($ont->cto->ceo_splitter->ceo->dio->olt);
        $this->interfaces = [$ont->interface];
        $this->serials = [$ont->name];

        return $this;
    }

    public function onts(Collection $onts): mixed
    {
        if ($onts->isEmpty()) {
            throw new Exception('Onts collections is empty.');
        }

        if (! ($onts->first() instanceof Ont)) {
            throw new Exception('The given object model is not an Ont.');
        }

        $this->connect($onts->first()->cto->ceo_splitter->ceo->dio->olt);

        $onts->each(function ($ont) {
            if ($ont instanceof Ont) {
                $this->interfaces[] = $ont->interface;
                $this->serials[] = $ont->name;
            }
        });

        return $this;
    }

    public function interface(string $interface): mixed
    {
        $this->interfaces = [$interface];

        return $this;
    }

    public function interfaces(array $interfaces): mixed
    {
        $this->interfaces = $interfaces;

        return $this;
    }

    public function serial(string $serial): mixed
    {
        $this->serials = [$serial];

        return $this;
    }

    public function serials(array $serials): mixed
    {
        $this->serials = $serials;

        return $this;
    }

    public function opticalPower(): float|array
    {
        if (empty($this->interfaces)) {
            throw new Exception('Interface(s) not found.');
        }

        if ($this->model === 'C300') {
            return (new C300($this->connection))->ontOpticalPower($this->interfaces);
        }

        if ($this->model === 'C600') {
            return (new C600($this->connection))->ontOpticalPower($this->interfaces);
        }

        throw new Exception('Model '.$this->model.' is not supported.');
    }

    public function opticalInterface(): string|array
    {
        if ($this->model === 'C300') {
            return (new C300($this->connection))->ontInterface($this->serials);
        }

        if ($this->model === 'C600') {
            return (new C600($this->connection))->ontInterface($this->serials);
        }

        throw new \Exception('Product model not supported');
    }

    // /**
    //  * Returns the ONT optical power
    //  */
    // public function ontOpticalPower(string $interface): float
    // {
    //     if ($this->model === 'C300') {
    //         return (new C300($this->connection))->ontOpticalPower($interface);
    //     }

    //     if ($this->model === 'C600') {
    //         return (new C600($this->connection))->ontOpticalPower($interface);
    //     }

    //     throw new \Exception('Product model not supported');
    // }

    // /**
    //  * Returns the ONT interface
    //  */
    // public function ontInterface(string $serial): string
    // {
    //     if ($this->model === 'C300') {
    //         return (new C300($this->connection))->ontInterface($serial);
    //     }

    //     if ($this->model === 'C600') {
    //         return (new C600($this->connection))->ontInterface($serial);
    //     }

    //     throw new \Exception('Product model not supported');
    // }
}
