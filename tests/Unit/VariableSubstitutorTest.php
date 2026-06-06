<?php

namespace Tests\Unit;

use App\Services\VariableSubstitutor;
use PHPUnit\Framework\TestCase;

class VariableSubstitutorTest extends TestCase
{
    public function test_it_replaces_known_tokens_and_leaves_unknown_intact(): void
    {
        $substitutor = new VariableSubstitutor;
        $variables = ['BASE_URL' => 'https://api.test', 'TOKEN' => 'abc'];

        $this->assertSame('https://api.test/users', $substitutor->substitute('{{BASE_URL}}/users', $variables));
        $this->assertSame('Bearer abc', $substitutor->substitute('Bearer {{ TOKEN }}', $variables));
        $this->assertSame('{{MISSING}}', $substitutor->substitute('{{MISSING}}', $variables));
    }

    public function test_it_substitutes_recursively_through_arrays(): void
    {
        $substitutor = new VariableSubstitutor;

        $result = $substitutor->substitute(
            ['url' => '{{BASE_URL}}', 'nested' => ['auth' => 'Bearer {{TOKEN}}']],
            ['BASE_URL' => 'https://x', 'TOKEN' => 'y'],
        );

        $this->assertSame(['url' => 'https://x', 'nested' => ['auth' => 'Bearer y']], $result);
    }
}
