<?php

namespace App\Tests\Helper;

use App\Helper\PathHelper;
use PHPUnit\Framework\TestCase;

class PathHelperTest extends TestCase
{
    /**
     * @return iterable
     */
    public function getPaths(): iterable
    {
        yield [['path1', 'path2', 'path3'], 'path1/path2/path3'];
        yield [['/path1', 'path2/', 'path3'], '/path1/path2/path3'];
        yield [['/path1/aze/a', 'path2/', 'path3'], '/path1/aze/a/path2/path3'];
        yield [['\path1/aze\b', 'path2/', 'path3'], '/path1/aze/b/path2/path3'];
    }

    /**
     * @dataProvider getPaths
     * @param array $parts
     * @param string $expected
     */
    public function testPathGeneration(array $parts, string $expected): void
    {
        $path = call_user_func_array([PathHelper::class, 'join'], $parts);
        $this->assertEquals($expected, $path);
    }

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }
}
