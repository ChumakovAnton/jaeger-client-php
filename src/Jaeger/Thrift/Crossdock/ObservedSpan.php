<?php
namespace Jaeger\Thrift\Crossdock;

/**
 * Autogenerated by Thrift Compiler (0.11.0)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
use Thrift\Base\TBase;
use Thrift\Type\TType;
use Thrift\Type\TMessageType;
use Thrift\Exception\TException;
use Thrift\Exception\TProtocolException;
use Thrift\Protocol\TProtocol;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Exception\TApplicationException;


class ObservedSpan extends TBase {
  static $isValidate = false;

  static $_TSPEC = array(
    1 => array(
      'var' => 'traceId',
      'isRequired' => true,
      'type' => TType::STRING,
      ),
    2 => array(
      'var' => 'sampled',
      'isRequired' => true,
      'type' => TType::BOOL,
      ),
    3 => array(
      'var' => 'baggage',
      'isRequired' => true,
      'type' => TType::STRING,
      ),
    );

  /**
   * @var string
   */
  public $traceId = null;
  /**
   * @var bool
   */
  public $sampled = null;
  /**
   * @var string
   */
  public $baggage = null;

  public function __construct($vals=null) {
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'ObservedSpan';
  }

  public function read($input)
  {
    return $this->_read('ObservedSpan', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('ObservedSpan', self::$_TSPEC, $output);
  }

}
