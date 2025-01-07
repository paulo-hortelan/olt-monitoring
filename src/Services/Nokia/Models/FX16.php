<?php

namespace PauloHortelan\Onmt\Services\Nokia\Models;

use Exception;
use PauloHortelan\Onmt\DTOs\Nokia\FX16\EdOntConfig;
use PauloHortelan\Onmt\DTOs\Nokia\FX16\EdOntVeipConfig;
use PauloHortelan\Onmt\DTOs\Nokia\FX16\EntHguTr069SparamConfig;
use PauloHortelan\Onmt\DTOs\Nokia\FX16\EntLogPortConfig;
use PauloHortelan\Onmt\DTOs\Nokia\FX16\EntOntCardConfig;
use PauloHortelan\Onmt\DTOs\Nokia\FX16\EntOntConfig;
use PauloHortelan\Onmt\DTOs\Nokia\FX16\QosUsQueueConfig;
use PauloHortelan\Onmt\DTOs\Nokia\FX16\VlanEgPortConfig;
use PauloHortelan\Onmt\DTOs\Nokia\FX16\VlanPortConfig;
use PauloHortelan\Onmt\Models\CommandResult;
use PauloHortelan\Onmt\Services\Nokia\NokiaService;

class FX16 extends NokiaService
{
    /**
     * Inhibit environment alarms - Telnet
     */
    public static function environmentInhibitAlarms(): ?CommandResult
    {
        $command = 'environment inhibit-alarms';

        try {
            self::$telnetConn->exec($command);

            return CommandResult::make([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::make([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Executes the given command - Telnet
     */
    public static function executeCommandTelnet(string $command): ?CommandResult
    {
        try {
            self::$telnetConn->exec($command);

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Executes the given command - TL1
     */
    public static function executeCommandTL1(string $command): ?CommandResult
    {
        try {
            self::$tl1Conn->exec($command);

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Get ONTs info by PON interface - Telnet
     */
    public static function showEquipmentOntStatusPon(string $ponInterface): ?CommandResult
    {
        $command = "show equipment ont status pon $ponInterface detail";

        try {
            $response = self::$telnetConn->exec($command);

            if (! str_contains($response, 'sernum')) {
                throw new \Exception($response);
            }

            $ontsData = [];
            $ontsBlocks = explode("\n--------------------------------------------------------------------------------\n", $response);

            foreach ($ontsBlocks as $ontBlock) {

                if (empty(trim($ontBlock))) {
                    continue;
                }

                $ontAttributes = [
                    'pon-interface' => null,
                    'interface' => null,
                    'sernum' => null,
                    'admin-status' => null,
                    'oper-status' => null,
                    'olt-rx-sig-level' => null,
                    'ont-olt-distance' => null,
                    'desc1' => null,
                    'desc2' => null,
                    'hostname' => null,
                ];

                $lines = explode("\n", $ontBlock);
                foreach ($lines as $line) {
                    $line = trim($line);

                    $patterns = [
                        'pon-interface' => '/pon\s*:\s*(\S+)/',
                        'interface' => '/ont\s*:\s*(\S+)/',
                        'sernum' => '/sernum\s*:\s*(\S+)/',
                        'admin-status' => '/admin-status\s*:\s*(\S+)/',
                        'oper-status' => '/oper-status\s*:\s*(\S+)/',
                        'olt-rx-sig-level' => '/olt-rx-sig-level\(dbm\)\s*:\s*([\S-]+)/',
                        'ont-olt-distance' => '/ont-olt-distance\(km\)\s*:\s*([\S-]+)/',
                        'desc1' => '/desc1\s*:\s*(.+)/',
                        'desc2' => '/desc2\s*:\s*(.+)/',
                        'hostname' => '/hostname\s*:\s*(\S+)/',
                    ];

                    foreach ($patterns as $key => $pattern) {
                        if (preg_match($pattern, $line, $matches)) {
                            $value = trim($matches[1]);

                            if (empty($value)) {
                                $ontAttributes[$key] = null;
                            } elseif (in_array($key, ['olt-rx-sig-level', 'ont-olt-distance'])) {
                                $ontAttributes[$key] = (float) $value;
                            } else {
                                $ontAttributes[$key] = $value;
                            }
                        }
                    }
                }

                if (! empty($ontAttributes['pon-interface'])) {
                    $ontsData[] = $ontAttributes;
                }
            }
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }

        return CommandResult::create([
            'success' => true,
            'command' => $command,
            'error' => null,
            'result' => $ontsData,
        ]);
    }

    /**
     * Configure ONT admin state - Telnet
     */
    public static function configureEquipmentOntInterfaceAdminState(string $interface, string $adminState): ?CommandResult
    {
        $adminStates = ['down', 'up'];

        if (! in_array($adminState, $adminStates)) {
            throw new Exception("AdminState must be 'down' or 'up'");
        }

        $command = "configure equipment ont interface $interface admin-state $adminState";

        try {
            self::$telnetConn->exec($command);

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Remove ONT interface - Telnet
     */
    public static function configureEquipmentOntNoInterface(string $interface): ?CommandResult
    {
        $command = "configure equipment ont no interface $interface";

        try {
            self::$telnetConn->exec($command);

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Returns the ONT equipment optics - Telnet
     */
    public static function showEquipmentOntOptics(string $interface): ?CommandResult
    {
        $command = "show equipment ont optics $interface detail";

        try {
            $response = self::$telnetConn->exec($command);

            if (! str_contains($response, 'tx-signal-level')) {
                throw new \Exception($response);
            }

            $ontDetails = [
                'tx-signal-level' => null,
                'ont-voltage' => null,
                'olt-rx-sig-level' => null,
                'rx-signal-level' => null,
                'ont-temperature' => null,
                'laser-bias-curr' => null,
            ];

            $lines = explode("\n", $response);

            foreach ($lines as $line) {
                $line = trim($line);

                foreach ($ontDetails as $key => $value) {
                    if (preg_match('/'.preg_quote($key, '/').'\s*:\s*([^\s:]+)/i', $line, $matches)) {
                        $value = trim($matches[1]);

                        $ontDetails[$key] = ! empty($value) ? (float) $value : null;
                    }
                }
            }

            extract($ontDetails);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }

        return CommandResult::create([
            'success' => true,
            'command' => $command,
            'error' => null,
            'result' => $ontDetails,
        ]);
    }

    /**
     * Returns the ONT optical interface - Telnet
     */
    public static function showEquipmentOntIndex(string $serial): ?CommandResult
    {
        $formattedSerial = substr_replace($serial, ':', 4, 0);
        $command = "show equipment ont index sn:$formattedSerial detail";

        try {
            $response = self::$telnetConn->exec($command);

            if (! str_contains($response, 'ont-idx')) {
                throw new \Exception($response);
            }

            if (preg_match('/ont-idx.*:(.*\s)/m', $response, $match)) {
                $interface = trim($match[1]);
            }

            $ontInterface = [
                'interface' => $interface ?? null,
            ];

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => $ontInterface,
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }

        return $ontsInterface;
    }

    /**
     * Returns the ONT interface details - Telnet
     */
    public static function showEquipmentOntInterface(string $interface): ?CommandResult
    {
        $command = "show equipment ont interface $interface detail";

        try {
            $response = self::$telnetConn->exec($command);

            if (! str_contains($response, 'ont-idx')) {
                throw new \Exception($response);
            }

            $interfaceDetails = [
                'sw-ver-act' => null,
                'vendor-id' => null,
                'equip-id' => null,
                'actual-num-slots' => null,
                'num-tconts' => null,
                'num-prio-queues' => null,
                'auto-sw-download-ver' => null,
                'yp-serial-no' => null,
                'oper-spec-ver' => null,
                'act-txpower-ctrl' => null,
                'cfgfile1-ver-act' => null,
                'cfgfile2-ver-act' => null,
                'actual-us-rate' => null,
                'template-name' => null,
                'auto-prov-status' => null,
                'eqpt-ver-num' => null,
                'sw-ver-psv' => null,
                'version-number' => null,
                'num-trf-sched' => null,
                'auto-sw-planned-ver' => null,
                'sernum' => null,
                'act-ont-type' => null,
                'sn-bundle-status' => null,
                'cfgfile1-ver-psv' => null,
                'cfgfile2-ver-psv' => null,
            ];

            $lines = explode("\n", $response);

            foreach ($lines as $line) {
                $line = trim($line);

                foreach ($interfaceDetails as $key => $value) {
                    if (preg_match('/'.preg_quote($key, '/').'\s*:\s*([^\s:]+)/i', $line, $matches)) {
                        $value = trim($matches[1]);

                        if (empty($value)) {
                            $interfaceDetails[$key] = null;
                        } elseif (in_array($key, ['num-tconts', 'num-prio-queues', 'actual-num-slots', 'num-trf-sched'])) {
                            $interfaceDetails[$key] = (int) $value;
                        } else {
                            $interfaceDetails[$key] = $value;
                        }
                    }
                }
            }

            extract($interfaceDetails);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }

        return CommandResult::create([
            'success' => true,
            'command' => $command,
            'error' => null,
            'result' => $interfaceDetails,
        ]);
    }

    /**
     * Returns the ONT software download details - Telnet
     */
    public static function showEquipmentOntSwDownload(string $interface): ?CommandResult
    {
        $command = "show equipment ont sw-download $interface";

        try {
            $response = self::$telnetConn->exec($command);

            if (! str_contains($response, 'ont-idx')) {
                throw new \Exception($response);
            }

            $swDownloadDetails = [
                'inactive' => null,
                'download-notok' => null,
                'ntlt-inprogress' => null,
                'ontflash-inprogress' => null,
                'ntlt-failure' => null,
                'ontflash-failure' => null,
                'download-file-notfound' => null,
                'sw-version-mismatch' => null,
                'sw-delayactivate' => null,
                'planned' => null,
                'planned-notok' => null,
                'download-inprogress' => null,
                'omci-inprogress' => null,
                'ontswact-inprogress' => null,
                'omci-failure' => null,
                'ontswact-failure' => null,
                'no-matching-software' => null,
                'sw-download-failure' => null,
                'sw-download-pending' => null,
            ];

            $lines = explode("\n", $response);

            foreach ($lines as $line) {
                $line = trim($line);

                foreach ($swDownloadDetails as $key => $value) {
                    if (preg_match('/'.preg_quote($key, '/').'\s*:\s*([^\s:]+)/i', $line, $matches)) {
                        $value = trim($matches[1]);

                        if (empty($value)) {
                            $swDownloadDetails[$key] = null;
                        } else {
                            $swDownloadDetails[$key] = $value;
                        }
                    }
                }
            }

            extract($swDownloadDetails);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }

        return CommandResult::create([
            'success' => true,
            'command' => $command,
            'error' => null,
            'result' => $swDownloadDetails,
        ]);
    }

    /**
     * Returns the ONT port details - Telnet
     */
    public static function showInterfacePort(string $interface): ?CommandResult
    {
        $command = "show interface port ont:$interface detail";

        try {
            $response = self::$telnetConn->exec($command);

            if (! str_contains($response, 'opr-status')) {
                throw new \Exception($response);
            }

            if (preg_match('/opr-status.*?:(.*?[^\s]+)/m', $response, $match)) {
                $oprStatus = trim($match[1]);
            }

            if (preg_match('/admin-status.*?:(.*?[^\s]+)/m', $response, $match)) {
                $adminStatus = trim($match[1]);
            }

            if (preg_match('/last-chg-opr-stat.*?:(.*?[^\s]+)/m', $response, $match)) {
                $lastChgOprStat = trim($match[1]);
            }

            $portDetail[] = [
                'interface' => $interface,
                'oprStatus' => $oprStatus ?? null,
                'adminStatus' => $adminStatus ?? null,
                'lastChgOprStat' => $lastChgOprStat ?? null,
            ];

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => $portDetail,
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Returns the ONTs unprovisioned - Telnet
     */
    public static function showPonUnprovisionOnu(): ?CommandResult
    {
        $unregData = [];
        $command = 'show pon unprovision-onu';

        try {
            $response = self::$telnetConn->exec($command);

            if (! str_contains($response, 'gpon-index')) {
                throw new \Exception($response);
            }

            $splittedResponse = preg_split("/\r\n|\n|\r/", $response);

            foreach ($splittedResponse as $key => $column) {
                if (preg_match('/gpon-index/', $column)) {
                    $numOnts = count($splittedResponse) - $key - 5;

                    if ($numOnts === 0) {
                        return CommandResult::create([
                            'success' => true,
                            'command' => $command,
                            'error' => null,
                            'result' => [],
                        ]);
                    }

                    for ($i = 1; $i <= $numOnts; $i++) {
                        $splitted = preg_split('/\s+/', $splittedResponse[$key + $i + 1]);

                        $unregData[] = [
                            'alarm-idx' => (int) $splitted[1] ?? null,
                            'interface' => $splitted[2] ?? null,
                            'serial' => $splitted[3] ?? null,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }

        return CommandResult::create([
            'success' => true,
            'command' => $command,
            'error' => null,
            'result' => $unregData,
        ]);
    }

    /**
     * Inhibit all messages - TL1
     */
    public static function inhMsgAll(): ?CommandResult
    {
        $command = 'INH-MSG-ALL::ALL:::;';

        try {
            $response = self::$tl1Conn->exec($command, false);

            if (! str_contains($response, 'M  0 COMPLD')) {
                throw new \Exception($response);
            }

            return CommandResult::make([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::make([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Provision ONT - TL1
     */
    public static function entOnt(string $interface, EntOntConfig $config): ?CommandResult
    {
        $formattedInterface = str_replace('/', '-', $interface);

        $entOntCommand = $config->buildCommand();
        $command = "ENT-ONT::ONT-$formattedInterface::::$entOntCommand;";

        try {
            $response = self::$tl1Conn->exec($command, false);

            if (! str_contains($response, 'M  0 COMPLD')) {
                throw new \Exception($response);
            }

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Edit provisioned ONT - TL1
     */
    public static function edOnt(string $interface, EdOntConfig $config): ?CommandResult
    {
        $formattedInterface = str_replace('/', '-', $interface);

        $edOntCommand = $config->buildCommand();
        $command = "ED-ONT::ONT-$formattedInterface::::$edOntCommand:IS;";

        try {
            $response = self::$tl1Conn->exec($command, false);

            if (! str_contains($response, 'M  0 COMPLD')) {
                throw new \Exception($response);
            }

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Plans a new ONT card - TL1
     */
    public static function entOntsCard(string $interface, EntOntCardConfig $config): ?CommandResult
    {
        $entOntCardConfigCommand = $config->buildCommand();
        $accessIdentifier = $config->buildIdentifier($interface);
        $command = "ENT-ONTCARD::$accessIdentifier:::$entOntCardConfigCommand::IS;";

        try {
            $response = self::$tl1Conn->exec($command, false);

            if (! str_contains($response, 'M  0 COMPLD')) {
                throw new \Exception($response);
            }

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Creates a logical port on an LT - TL1
     */
    public static function entLogPort(string $interface, EntLogPortConfig $config): ?CommandResult
    {
        $accessIdentifier = $config->buildIdentifier($interface);
        $command = "ENT-LOGPORT::$accessIdentifier:::;";

        try {
            $response = self::$tl1Conn->exec($command, false);

            if (! str_contains($response, 'M  0 COMPLD')) {
                throw new \Exception($response);
            }

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Edit ONT VEIP - TL1
     */
    public static function edOntVeip(string $interface, EdOntVeipConfig $config): ?CommandResult
    {
        $accessIdentifier = $config->buildIdentifier($interface);
        $command = "ED-ONTVEIP::$accessIdentifier:::::IS;";

        try {
            $response = self::$tl1Conn->exec($command, false);

            if (! str_contains($response, 'M  0 COMPLD')) {
                throw new \Exception($response);
            }

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Configures upstream queue - TL1
     */
    public static function setQosUsQueue(string $interface, QosUsQueueConfig $config): ?CommandResult
    {
        $accessIdentifier = $config->buildIdentifier($interface);
        $buildCommand = $config->buildCommand();
        $command = "SET-QOS-USQUEUE::$accessIdentifier::::$buildCommand;";

        try {
            $response = self::$tl1Conn->exec($command, false);

            if (! str_contains($response, 'M  0 COMPLD')) {
                throw new \Exception($response);
            }

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Bounds a bridge port to the VLAN - TL1
     */
    public static function setVlanPort(string $interface, VlanPortConfig $config): ?CommandResult
    {
        $accessIdentifier = $config->buildIdentifier($interface, 14, 1);
        $buildCommand = $config->buildCommand();
        $command = "SET-VLANPORT::$accessIdentifier:::$buildCommand;";

        try {
            $response = self::$tl1Conn->exec($command, false);

            if (! str_contains($response, 'M  0 COMPLD')) {
                throw new \Exception($response);
            }

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Adds a egress port to the VLAN - TL1
     */
    public static function entVlanEgPort(string $interface, VlanEgPortConfig $config): ?CommandResult
    {
        $accessIdentifier = $config->buildIdentifier($interface, 14, 1);
        $buildCommand = $config->buildCommand();
        $command = "ENT-VLANEGPORT::$accessIdentifier:::$buildCommand;";

        try {
            $response = self::$tl1Conn->exec($command, false);

            if (! str_contains($response, 'M  0 COMPLD')) {
                throw new \Exception($response);
            }

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }

    /**
     * Provisions a new HGU TR069 short key-value pair - TL1
     */
    public static function entHguTr069Sparam(string $interface, EntHguTr069SparamConfig $config): ?CommandResult
    {
        $accessIdentifier = $config->buildIdentifier($interface, 14, 1);
        $buildCommand = $config->buildCommand();
        $command = "ENT-HGUTR069-SPARAM::$accessIdentifier::::$buildCommand;";

        try {
            $response = self::$tl1Conn->exec($command, false);

            if (! str_contains($response, 'M  0 COMPLD')) {
                throw new \Exception($response);
            }

            return CommandResult::create([
                'success' => true,
                'command' => $command,
                'error' => null,
                'result' => [],
            ]);
        } catch (\Exception $e) {
            return CommandResult::create([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
                'result' => [],
            ]);
        }
    }
}
