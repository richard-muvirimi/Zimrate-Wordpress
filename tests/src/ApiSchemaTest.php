<?php declare(strict_types=1);

/**
 * File for zimrate api schema tests
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.1.6
 * @version 1.1.6
 */

namespace RichardMuvirimi\Zimrate\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use RichardMuvirimi\Zimrate\Helpers\Functions;

/**
 * Test the zimrate GraphQL schema still exposes what the plugin reads.
 *
 * The api dropping a field or renaming an enum value is silent at runtime, the
 * rates table just empties out, so the drift is caught here instead.
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.1.6
 * @version 1.1.6
 */
class ApiSchemaTest extends TestCase
{
    // Adds Mockery expectations to the PHPUnit assertions count.
    use MockeryPHPUnitIntegration;

    /**
     * Cached introspection result, false when unreachable, fetched once per run
     *
     * @var array|false|null
     */
    private static $schema;

    /**
     * Test the rate query still returns the fields the plugin reads
     *
     * @return void
     */
    public function testRateTypeProvidesFieldsThePluginReads(): void
    {
        $schema = $this->getSchema();

        $fields = array_column($schema['rate']['fields'], 'name');

        // read by Functions::get_rates() and the admin rates table
        foreach (['currency', 'rate', 'last_checked', 'last_updated'] as $field) {
            self::assertContains(
                $field,
                $fields,
                'Rate.' . $field . ' is gone from the zimrate api, Functions::get_rates() reads it'
            );
        }
    }

    /**
     * Test the query still exposes the rate and info entry points
     *
     * @return void
     */
    public function testQueryProvidesRateAndInfo(): void
    {
        $schema = $this->getSchema();

        $fields = array_column($schema['query']['fields'], 'name');

        self::assertContains('rate', $fields, 'Query.rate is gone from the zimrate api');
        self::assertContains('info', $fields, 'Query.info is gone from the zimrate api');
    }

    /**
     * Test the rate query still takes the argument the plugin binds
     *
     * @return void
     */
    public function testRateQueryAcceptsPreferArgument(): void
    {
        $schema = $this->getSchema();

        $args = [];
        foreach ($schema['query']['fields'] as $field) {
            if ($field['name'] === 'rate') {
                $args = $field['args'];
                break;
            }
        }

        $types = [];
        foreach ($args as $arg) {
            $types[$arg['name']] = $arg['type']['name'] ?? ($arg['type']['ofType']['name'] ?? '');
        }

        self::assertSame(
            'Prefer',
            $types['prefer'] ?? '',
            'rate(prefer:) no longer takes a Prefer, Functions::get_rates() binds it as one'
        );
    }

    /**
     * Test the prefer enum still covers the options the settings page offers
     *
     * @return void
     */
    public function testPreferEnumCoversTheSettingsOptions(): void
    {
        $schema = $this->getSchema();

        $prefers = array_column($schema['prefer']['enumValues'], 'name');

        self::assertNotEmpty(
            $prefers,
            'The Prefer enum is empty, Functions::supported_prefers() builds the settings dropdown from it'
        );

        self::assertContains(
            Functions::default_prefer(),
            $prefers,
            'Functions::default_prefer() is no longer a value the api accepts'
        );

        // whatever a site already stored has to keep resolving once uppercased
        foreach (['max', 'mean', 'median', 'min'] as $stored) {
            self::assertContains(
                strtoupper($stored),
                $prefers,
                'Sites have "' . $stored . '" stored but the Prefer enum has no ' . strtoupper($stored)
            );
        }
    }

    /**
     * Introspect the live schema, skipping the test when the api is unreachable
     *
     * @return array
     */
    private function getSchema(): array
    {
        if (self::$schema === null) {
            self::$schema = $this->introspect();
        }

        if (self::$schema === false) {
            self::markTestSkipped('The zimrate api could not be reached');
        }

        return self::$schema;
    }

    /**
     * Fetch the parts of the schema the plugin depends on
     *
     * @return array|false
     */
    private function introspect()
    {
        $query = 'query Schema {' .
            ' query: __type(name: "Query") {' .
            ' fields { name args { name type { kind name ofType { kind name } } } }' .
            ' }' .
            ' rate: __type(name: "Rate") { fields { name } }' .
            ' prefer: __type(name: "Prefer") { enumValues { name } }' .
            ' }';

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode(['query' => $query]),
                'ignore_errors' => true,
                'timeout' => 30,
            ],
        ]);

        $response = @file_get_contents(Functions::get_api_url(), false, $context);

        if ($response === false) {
            return false;
        }

        $body = json_decode($response, true);

        // the api answers with a 200 even for a failed query
        if (!is_array($body) || !empty($body['errors']) || !isset($body['data'])) {
            return false;
        }

        return $body['data'];
    }

    /**
     * Tear Down
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * SetUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }
}
