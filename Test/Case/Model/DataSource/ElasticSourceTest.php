<?php
App::uses('Model', 'Model');

class ElasticSourceTest extends CakeTestCase {
	
	public $fixtures = array('plugin.elastic.elastic_test_model');

/**
 * Setup each test.
 *
 * @return void
 * @author David Kullmann
 */
	public function setUp() {
		$this->Es = ConnectionManager::getDataSource('test_elasticsearch');
		if (!($this->Es instanceof ElasticSource)) {
			$this->markTestSkipped('Unable to load elastic_test datasource for ElasticSource');
		}
		$this->Model = ClassRegistry::init('TestModel');
	}

/**
 * Teardown each tests.
 *
 * @return void
 * @author David Kullmann
 */
	public function tearDown() {
		unset($this->Model);
		ClassRegistry::flush();
	}

/**
 * Undocumented function.
 *
 * @return void
 * @author David Kullmann
 * @expectedException MissingIndexException
 */
	public function testMissingIndexException() {
		$original_index = $this->Es->config['index'];
		
		$this->Es->config['index'] = 'a_new_fake_index';
		try {
			$result = $this->Es->describe($this->Model);
		} catch (MissingIndexException $e) {
			$this->Es->config['index'] = $original_index;
			throw $e;
		}
	}

/**
 * Test the getType method.
 *
 * @return void
 * @author David Kullmann
 */
	public function testGetType() {
		$expected = 'test_models';
		$result = $this->Es->getType($this->Model);
		
		$this->assertEquals($expected, $result);
		
		$expected = 'custom_type';
		$this->Model->useType = 'custom_type';
		
		$result = $this->Es->getType($this->Model);
		
		$this->assertEquals($expected, $result);
	}

/**
 * Test creating and dropping a mapping.
 *
 * @return void
 * @author David Kullmann
 */
	public function testMapping() {
		
		$Unmapped = new Model(array('table' => 'map_test', 'name' => 'MapTest', 'ds' => 'test_elasticsearch'));
		
		$description = array(
			$Unmapped->alias => array(
				'id' => array(
					'key' => 'primary',
					'length' => 11,
					'type' => 'integer'
				),
				'string' => array(
					'type' => 'string',
					'length' => 255,
					'null' => false,
					'default' => null
				)
			)
		);
		
		$expected = true;
		$result = $this->Es->mapModel($Unmapped, $description);
		$this->assertEquals($expected, $result);

		$expected = array(
			'id' => array('type' => 'integer', 'length' => 11),
			'string' => array('type' => 'string')
		);
		$result = $this->Es->describe($Unmapped);
		$this->assertEquals($expected, $result);
		
		$expected = true;
		$result = $this->Es->checkMapping($Unmapped);
		$this->assertEquals($expected, $result);
		
		$expected = true;
		$result = $this->Es->dropMapping($Unmapped);
		$this->assertEquals($expected, $result);
		
		$expected = false;
		$result = $this->Es->checkMapping($Unmapped);
		$this->assertEquals($expected, $result);
		
		// Also parse descriptions w/no alias
		$description = $description[$Unmapped->alias];
		
		$expected = true;
		$result = $this->Es->mapModel($Unmapped, $description);
		$this->assertEquals($expected, $result);

		$expected = array(
			'id' => array('type' => 'integer', 'length' => 11),
			'string' => array('type' => 'string')
		);
		$result = $this->Es->describe($Unmapped);
		$this->assertEquals($expected, $result);
	}

/**
 * Test parsing mappings for multiple types, multiple models, etc.
 *
 * @return void
 * @author David Kullmann
 */
	public function testParseMapping() {
		$mapping = array('index' => array(
			'type' => array(
				'properties' => array(
					'Alias' => array(
						'properties' => array(
							'id' => array('type' => 'integer'),
							'string' => array('type' => 'string')
						)
					)
				)
			)
		));
		
		$expected = array('Alias' => array(
			'id' => array('type' => 'integer'),
			'string' => array('type' => 'string')
		));
		$result = $this->Es->parseMapping($mapping);
		$this->assertEquals($expected, $result);
		
		$mapping = array('index' => array(
			'type' => array(
				'properties' => array(
					'Alias' => array(
						'properties' => array(
							'id' => array('type' => 'integer'),
							'string' => array('type' => 'string')
						)
					),
					'RelatedModel' => array(
						'properties' => array(
							'id' => array('type' => 'integer'),
							'float' => array('type' => 'float')
						)
					)
				)
			)
		));
		
		$expected = array(
			'Alias' => array(
				'id' => array('type' => 'integer'),
				'string' => array('type' => 'string')
			),
			'RelatedModel' => array(
				'id' => array('type' => 'integer'),
				'float' => array('type' => 'float')
			)
		);
		$result = $this->Es->parseMapping($mapping);
		$this->assertEquals($expected, $result);
		
		$expected = array('type');
		$result = $this->Es->parseMapping($mapping, true);
		$this->assertEquals($expected, $result);
		
		$mapping = array('index' => array(
			'type' => array(
				'properties' => array(
					'Alias' => array(
						'properties' => array(
							'id' => array('type' => 'integer'),
							'string' => array('type' => 'string')
						)
					)
				)
			),
			'type2' => array(
				'properties' => array(
					'AnotherModel' => array(
						'properties' => array(
							'id' => array('type' => 'integer'),
							'string' => array('type' => 'string')
						)
					)
				)
			)
		));
		
		$expected = array(
			'Alias' => array(
				'id' => array('type' => 'integer'),
				'string' => array('type' => 'string')
			),
			'AnotherModel' => array(
				'id' => array('type' => 'integer'),
				'string' => array('type' => 'string')
			)
		);
		$result = $this->Es->parseMapping($mapping);
		$this->assertEquals($expected, $result);
		
		$expected = array('type', 'type2');
		$result = $this->Es->parseMapping($mapping, true);
		$this->assertEquals($expected, $result);	
	}

/**
 * Create and map a model.
 *
 * @return object Model
 */
	private function __mapModel($name) {
		$Model = new Model(array('table' => 'map_test', 'name' => $name, 'ds' => 'test_elasticsearch'));
		$mapping = array(
			$Model->alias => array(
				'id' => array(
					'type' => 'integer',
					'length' => 36
				),
				'string' => array(
					'type' => 'string'
				)
			)
		);
		$this->Es->mapModel($Model, $mapping);

		return $Model;
	}

/**
 * Test simple conditions.
 *
 * @return void
 */
	public function testParseConditionsSimple() {
		$Model = $this->__mapModel('MapTest');

		$conditions = array(
			'string' => 'test'
		);
		$expected = array(
			array(
				'term' => array(
					'string' => 'test'
				)
			)
		);
		$result = $this->Es->parseConditions($Model, $conditions);
		$this->assertEquals($expected, $result);

		$conditions = array(
			'MapTest.string' => 'test'
		);
		$expected = array(
			array(
				'term' => array(
					'MapTest.string' => 'test'
				)
			)
		);
		$result = $this->Es->parseConditions($Model, $conditions);
		$this->assertEquals($expected, $result);
	}

/**
 * Test boolean conditions.
 *
 * @return void
 */
	public function testParseConditionsBoolean() {
		$Model = $this->__mapModel('MapTest');

		// multiple
		$conditions = array(
			'bool' => array(
				'MapTest.id must =' => array(1, 2, 3)
			)
		);
		$expected = array(
			array(
				'bool' => array(
					'must' => array(
						array(
							'terms' => array(
								'MapTest.id' => array(1, 2, 3)
							)
						)
					)
				)
			)
		);
		$result = $this->Es->parseConditions($Model, $conditions);
		$this->assertEquals($expected, $result);

		// single
		$expected = array(
			array(
				'bool' => array(
					'must' => array(
						array(
							'term' => array(
								'MapTest.id' => 1
							)
						)
					)
				)
			)
		);

		$conditions = array(
			'bool' => array(
				'MapTest.id must =' => 1
			)
		);
		$result = $this->Es->parseConditions($Model, $conditions);
		$this->assertEquals($expected, $result);

		$conditions = array(
			'bool' => array(
				'MapTest.id must =' => array(1)
			)
		);
		$result = $this->Es->parseConditions($Model, $conditions);
		$this->assertEquals($expected, $result);
	}

/**
 * Test query string conditions.
 *
 * @return void
 */
	public function testParseConditionsQueryString() {
		$Model = $this->__mapModel('MapTest');

		$conditions = array(
			'query_string' => array(
				'fields' => array('MapTest.string'),
				'query' => 'Text to be looked up'
		    )
		);
		$expected = array(
			array(
				'query_string' => array(
					'fields' => array('MapTest.string'),
					'query' => 'Text to be looked up'
				)
			)
		);
		$result = $this->Es->parseConditions($Model, $conditions);
		$this->assertEquals($expected, $result);
	}
}
?>
