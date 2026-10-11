<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service\Meta;

interface MetaChannelInterface
{
    public function name(): string;

    /**
     * Envía un mensaje de texto.
     * @return array{ok: bool, wamid: ?string, error: ?string}
     */
    public function sendText(string $to, string $text): array;
}
