<?php
class TestModeTest extends CakeTestCase {

	public $fixtures = array('plugin.elastic.elastic_test_model');

/**
 * Setup each test.
 *
 * @return void
 * @author David Kullmann
 */
	public function setUp() {
		$this->Model = new Model(array('table' => 'test_models', 'name' => 'TestModel', 'ds' => 'test_elasticsearch'));
	}

/**
 * Teardown each tests.
 *
 * @return void
 * @author David Kullmann
 */
	public function tearDown() {
		/*$log = $this->Model->getDataSource()->getLog();
		foreach ($log['log'] as $query) {
			echo $query['query'] . "\n\n";
		}*/
		unset($this->Model);
	}

/**
 * Make sure our test is setup right.
 *
 * @return void
 * @author David Kullmann
 */
	// public function testInstance() {
	//      $this->ds = ConnectionManager::getDataSource($this->Model->useDbConfig);
	//      $this->assertTrue($ds instanceof ElasticSource);
	//      $this->assertEquals('test_index', $this->Model->useDbConfig);
	// }

/**
 * Test simple find.
 *
 * @return void
 */
	public function testFindAllSimple() {
		$expected = array(
			array(
				'TestModel' => array(
					'id' => 1,
					'string' => 'Analyzed for terms',
					'created' => '2012-01-01 00:00:00',
					'modified' => '2012-02-01 00:00:00'
				)
			),
			array(
				'TestModel' => array(
					'id' => 2,
					'string' => 'example',
					'created' => '2012-01-01 00:00:00',
					'modified' => '2012-02-01 00:00:00'
				)
			)
		);
		$result = $this->Model->find('all');
		$this->assertEquals($expected, $result);

		$expected = array(
			array(
				'TestModel' => array(
					'id' => 2,
					'string' => 'example',
					'created' => '2012-01-01 00:00:00',
					'modified' => '2012-02-01 00:00:00'
				)
			)
		);

		$params = array(
			'conditions' => array(
				'string' => 'example'
			)
		);
		$result = $this->Model->find('all', $params);
		$this->assertEquals($expected, $result);

		$params = array(
			'conditions' => array(
				'TestModel.string' => 'example'
			)
		);
		$result = $this->Model->find('all', $params);
		$this->assertEquals($expected, $result);
	}

/**
 * Test boolean find.
 *
 * @return void
 */
	public function testFindAllBoolean() {
		// filtered
		$expected = array(
			array(
				'TestModel' => array(
					'id' => 1,
					'string' => 'Analyzed for terms',
					'created' => '2012-01-01 00:00:00',
					'modified' => '2012-02-01 00:00:00'
				)
			)
		);

		$params = array(
			'conditions' => array(
				'bool' => array(
					'id must =' => array(1, 3)
				)
			)
		);
		$result = $this->Model->find('all', $params);
		$this->assertEquals($expected, $result);

		$params = array(
			'conditions' => array(
				'bool' => array(
					'TestModel.id must =' => array(1, 3)
				)
			)
		);
		$result = $this->Model->find('all', $params);
		$this->assertEquals($expected, $result);

		// query
		$params = array(
			'query' => array(
				'bool' => array(
					'TestModel.id must =' => array(1, 3)
				)
			)
		);
		$result = $this->Model->find('all', $params);
		$this->assertEquals($expected, $result);

		$expected = array(
			array(
				'TestModel' => array(
					'id' => 2,
					'string' => 'example',
					'created' => '2012-01-01 00:00:00',
					'modified' => '2012-02-01 00:00:00'
				)
			)
		);

		$params = array(
			'query' => array(
				'bool' => array(
					'TestModel.id must =' => array(2)
				)
			)
		);
		$result = $this->Model->find('all', $params);
		$this->assertEquals($expected, $result);

		$params = array(
			'query' => array(
				'bool' => array(
					'TestModel.id must =' => 2
				)
			)
		);
		$result = $this->Model->find('all', $params);
		$this->assertEquals($expected, $result);
	}

/**
 * Test QueryString find.
 *
 * @return void
 */
	public function testFindAllQueryString() {
		// filtered
		$expected = array(
			array(
				'TestModel' => array(
					'id' => 1,
					'string' => 'Analyzed for terms',
					'created' => '2012-01-01 00:00:00',
					'modified' => '2012-02-01 00:00:00'
				)
			)
		);

		$params = array(
			'conditions' => array(
				'query_string' => array(
					'fields' => array('TestModel.string'),
					'query' => 'Analyzed for terms'
				)
			)
		);
		$result = $this->Model->find('all', $params);
		$this->assertEquals($expected, $result);

		// query
		$params = array(
			'query' => array(
				'query_string' => array(
					'fields' => array('TestModel.string'),
					'query' => 'Analyzed for terms'
				)
			)
		);
		$result = $this->Model->find('all', $params);
		$this->assertEquals($expected, $result);
	}
}
?>
