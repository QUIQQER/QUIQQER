<?php

namespace QUI\System\Console\Completion;

use QUI\Exception;

use function basename;
use function dirname;
use function escapeshellarg;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function str_contains;
use function str_ends_with;
use function strtolower;

use const FILE_APPEND;
use const PHP_EOL;

class Installer
{
    private const START_MARKER = '# >>> QUIQQER console completion >>>';
    private const END_MARKER = '# <<< QUIQQER console completion <<<';

    private readonly string $configDirectory;

    public function __construct(
        private readonly string $homeDirectory,
        ?string $configDirectory = null
    ) {
        $this->configDirectory = $configDirectory ?: $homeDirectory . '/.config';
    }

    /**
     * @return array{completionFile: string, shellConfigFile: string|null}
     * @throws Exception
     */
    public function install(string $shell): array
    {
        $shell = strtolower(basename($shell));

        return match ($shell) {
            'bash' => $this->installBash(),
            'zsh' => $this->installZsh(),
            'fish' => $this->installFish(),
            default => throw new Exception(
                'Unsupported shell "' . $shell . '". Supported shells: bash, zsh, fish.'
            )
        };
    }

    /**
     * @return array{completionFile: string, shellConfigFile: string}
     * @throws Exception
     */
    private function installBash(): array
    {
        $completionFile = $this->configDirectory . '/quiqqer/completion.bash';
        $shellConfigFile = $this->homeDirectory . '/.bashrc';

        $this->writeCompletionFile($completionFile, <<<'BASH'
_quiqqer_console_completion()
{
    local line current command executable colon_prefix index
    local -a tokens

    line="${COMP_LINE:0:COMP_POINT}"
    current="${line##* }"
    read -r -a tokens <<< "$line"

    executable="${tokens[0]:-./console}"
    command=""

    if (( ${#tokens[@]} > 2 )) || [[ -z "$current" && ${#tokens[@]} -ge 2 ]]; then
        command="${tokens[1]}"
    fi

    mapfile -t COMPREPLY < <(
        "$executable" _complete --command="$command" --word="$current" 2>/dev/null
    )

    if [[ "$current" == *:* && "$COMP_WORDBREAKS" == *:* ]]; then
        colon_prefix="${current%:*}:"

        for index in "${!COMPREPLY[@]}"; do
            COMPREPLY[$index]="${COMPREPLY[$index]#"$colon_prefix"}"
        done
    fi
}

complete -F _quiqqer_console_completion console ./console
BASH);
        $this->addSourceToShellConfig($shellConfigFile, $completionFile);

        return [
            'completionFile' => $completionFile,
            'shellConfigFile' => $shellConfigFile
        ];
    }

    /**
     * @return array{completionFile: string, shellConfigFile: string}
     * @throws Exception
     */
    private function installZsh(): array
    {
        $completionFile = $this->configDirectory . '/quiqqer/completion.zsh';
        $shellConfigFile = $this->homeDirectory . '/.zshrc';

        $this->writeCompletionFile($completionFile, <<<'ZSH'
(( $+functions[compdef] )) || {
    autoload -Uz compinit
    compinit
}

_quiqqer_console_completion()
{
    local executable current command
    local -a suggestions

    executable="${words[1]}"
    current="${words[CURRENT]}"
    command=""

    if (( CURRENT > 2 )); then
        command="${words[2]}"
    fi

    suggestions=("${(@f)$("$executable" _complete --command="$command" --word="$current" 2>/dev/null)}")
    _describe 'QUIQQER console' suggestions
}

compdef _quiqqer_console_completion console ./console
ZSH);
        $this->addSourceToShellConfig($shellConfigFile, $completionFile);

        return [
            'completionFile' => $completionFile,
            'shellConfigFile' => $shellConfigFile
        ];
    }

    /**
     * @return array{completionFile: string, shellConfigFile: null}
     * @throws Exception
     */
    private function installFish(): array
    {
        $completionFile = $this->configDirectory . '/fish/completions/console.fish';

        $this->writeCompletionFile($completionFile, <<<'FISH'
function __quiqqer_console_complete
    set --local tokens (commandline -opc)
    set --local current (commandline -ct)
    set --local executable $tokens[1]
    set --local selected_command ''

    if test (count $tokens) -gt 1
        set selected_command $tokens[2]
    end

    $executable _complete --command="$selected_command" --word="$current" 2>/dev/null
end

complete --command console --no-files --arguments '(__quiqqer_console_complete)'
FISH);

        return [
            'completionFile' => $completionFile,
            'shellConfigFile' => null
        ];
    }

    /**
     * @throws Exception
     */
    private function writeCompletionFile(string $file, string $contents): void
    {
        $this->createDirectory(dirname($file));

        if (file_put_contents($file, $contents . PHP_EOL) === false) {
            throw new Exception('Could not write completion file: ' . $file);
        }
    }

    /**
     * @throws Exception
     */
    private function addSourceToShellConfig(string $shellConfigFile, string $completionFile): void
    {
        $contents = file_exists($shellConfigFile)
            ? file_get_contents($shellConfigFile)
            : '';

        if ($contents === false) {
            throw new Exception('Could not read shell configuration: ' . $shellConfigFile);
        }

        if (str_contains($contents, self::START_MARKER)) {
            return;
        }

        $prefix = $contents !== '' && !str_ends_with($contents, PHP_EOL)
            ? PHP_EOL
            : '';
        $source = '[ -f ' . escapeshellarg($completionFile) . ' ] && source ' . escapeshellarg($completionFile);
        $block = $prefix
            . self::START_MARKER . PHP_EOL
            . $source . PHP_EOL
            . self::END_MARKER . PHP_EOL;

        if (file_put_contents($shellConfigFile, $block, FILE_APPEND) === false) {
            throw new Exception('Could not update shell configuration: ' . $shellConfigFile);
        }
    }

    /**
     * @throws Exception
     */
    private function createDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new Exception('Could not create completion directory: ' . $directory);
        }
    }
}
