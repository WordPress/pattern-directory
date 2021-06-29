<?php

namespace A8C\Lib\Patterns;

require_once __DIR__ . '/BlockParser.php';

class NoopParser implements BlockParser {
	public function to_strings( array $block ) : array {
		return [];
	}

	public function replace_strings( array $block, array $replacements ) : array {
		return $block;
	}
}
