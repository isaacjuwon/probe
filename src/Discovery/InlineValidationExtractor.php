<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Discovery;

use ReflectionFunctionAbstract;
use Throwable;

final class InlineValidationExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extract(?ReflectionFunctionAbstract $reflection): array
    {
        if ($reflection === null) {
            return [];
        }

        try {
            $fileName = $reflection->getFileName();

            if ($fileName === false || ! file_exists(filename: $fileName)) {
                return [];
            }

            $startLine = $reflection->getStartLine();
            $endLine = $reflection->getEndLine();

            if ($startLine === false || $endLine === false) {
                return [];
            }

            $lines = file(filename: $fileName);

            if ($lines === false) {
                return [];
            }

            $body = implode(
                separator: '',
                array: array_slice(
                    array: $lines,
                    offset: $startLine - 1,
                    length: $endLine - $startLine + 1,
                ),
            );

            if (preg_match(pattern: '/->validate\(\s*\[(?P<rules>.*?)\]\s*\)/s', subject: $body, matches: $matches)) {
                return $this->parseRulesString(rulesBlock: $matches['rules']);
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private function parseRulesString(string $rulesBlock): array
    {
        $rules = [];

        if (preg_match_all(pattern: "/['\"](?P<key>[a-zA-Z0-9_\.]+)['\"]\s*=>\s*['\"](?P<val>[^'\"]+)['\"]/", subject: $rulesBlock, matches: $matches, flags: PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rules[$match['key']] = $match['val'];
            }
        }

        return $rules;
    }
}
