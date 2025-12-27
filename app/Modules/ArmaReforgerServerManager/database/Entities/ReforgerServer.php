<?php

namespace Flute\Modules\ArmaReforgerServerManager\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(repository: \Flute\Modules\ArmaReforgerServerManager\Database\Repositories\ReforgerServerRepository::class, table: 'reforger_servers')]
#[Index(columns: ['name'], unique: true)]
class ReforgerServer extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string', length: 255)]
    public string $name;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $installPath = null;

    #[Column(type: 'string', length: 255, default: '0.0.0.0')]
    public string $bindAddress = '0.0.0.0';

    #[Column(type: 'integer', default: 2001)]
    public int $bindPort = 2001;

    #[Column(type: 'string', length: 255, default: '')]
    public string $publicAddress = '';

    #[Column(type: 'integer', default: 2001)]
    public int $publicPort = 2001;

    #[Column(type: 'integer', default: 0)]
    public int $a2sPort = 0;

    #[Column(type: 'integer', default: 0)]
    public int $steamQueryPort = 0;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $adminPassword = null;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $serverPassword = null;

    #[Column(type: 'string', length: 255, default: 'Arma Reforger Server')]
    public string $serverName = 'Arma Reforger Server';

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $scenarioId = null;

    #[Column(type: 'integer', default: 64)]
    public int $maxPlayers = 64;

    #[Column(type: 'boolean', default: true)]
    public bool $visible = true;

    #[Column(type: 'boolean', default: false)]
    public bool $crossPlatform = false;

    #[Column(type: 'boolean', default: true)]
    public bool $battleEye = true;

    #[Column(type: 'boolean', default: false)]
    public bool $thirdPersonView = false;

    #[Column(type: 'boolean', default: false)]
    public bool $vonDisabled = false;

    #[Column(type: 'string', length: 50, default: 'stopped')]
    public string $status = 'stopped';

    #[Column(type: 'integer', nullable: true)]
    public ?int $pid = null;

    #[Column(type: 'text', nullable: true)]
    public ?string $configJson = null;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $installedVersion = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $lastStarted = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $lastStopped = null;

    #[Column(type: 'boolean', default: true)]
    public bool $enabled = true;

    #[HasMany(target: ReforgerServerMod::class, nullable: true)]
    public ?array $mods = null;

    /**
     * Get the full server configuration as array.
     */
    public function getConfig(): array
    {
        $baseConfig = [
            'bindAddress' => $this->bindAddress,
            'bindPort' => $this->bindPort,
            'publicAddress' => $this->publicAddress,
            'publicPort' => $this->publicPort,
            'a2s' => [
                'address' => $this->bindAddress,
                'port' => $this->a2sPort ?: $this->bindPort + 1,
            ],
            'steamQueryPort' => $this->steamQueryPort ?: $this->bindPort + 10,
        ];

        if ($this->adminPassword) {
            $baseConfig['game']['passwordAdmin'] = $this->adminPassword;
        }

        if ($this->serverPassword) {
            $baseConfig['game']['password'] = $this->serverPassword;
        }

        $baseConfig['game']['name'] = $this->serverName;
        $baseConfig['game']['scenarioId'] = $this->scenarioId;
        $baseConfig['game']['maxPlayers'] = $this->maxPlayers;
        $baseConfig['game']['visible'] = $this->visible;
        $baseConfig['game']['crossPlatform'] = $this->crossPlatform;
        $baseConfig['game']['supportedPlatforms'] = $this->crossPlatform ? ['PLATFORM_PC', 'PLATFORM_XBL'] : ['PLATFORM_PC'];
        $baseConfig['game']['gameProperties'] = [
            'serverMaxViewDistance' => 2500,
            'serverMinGrassDistance' => 50,
            'networkViewDistance' => 1000,
            'disableThirdPerson' => !$this->thirdPersonView,
            'fastValidation' => true,
            'battlEye' => $this->battleEye,
            'VONDisableUI' => $this->vonDisabled,
            'VONDisableDirectSpeechUI' => $this->vonDisabled,
        ];

        // Add mods if available
        if (!empty($this->mods)) {
            $baseConfig['game']['mods'] = [];
            foreach ($this->mods as $mod) {
                if ($mod->enabled) {
                    $baseConfig['game']['mods'][] = [
                        'modId' => $mod->mod->workshopId,
                        'name' => $mod->mod->name,
                        'version' => $mod->mod->version ?? '',
                    ];
                }
            }
        }

        // Merge custom config if exists
        if ($this->configJson) {
            $customConfig = json_decode($this->configJson, true);
            if ($customConfig) {
                $baseConfig = array_replace_recursive($baseConfig, $customConfig);
            }
        }

        return $baseConfig;
    }

    /**
     * Generate the server config JSON file content.
     */
    public function generateConfigFile(): string
    {
        return json_encode($this->getConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Check if the server process is running.
     */
    public function isRunning(): bool
    {
        if (!$this->pid) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            exec("tasklist /FI \"PID eq {$this->pid}\" 2>NUL | find \"{$this->pid}\"", $output);
            return !empty($output);
        }

        return file_exists("/proc/{$this->pid}");
    }
}
