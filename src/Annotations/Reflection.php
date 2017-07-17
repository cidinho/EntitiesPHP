<?php
namespace EntitiesPHP\Framework\Annotations;

use InvalidArgumentException;
use ReflectionClass;

/**
 * Description of Reflection
 *
 * @author alcides.bezerra
 */
class Reflection {
    private $className;
    private $reflection;
    
    public function __construct(string $className) {
        if(is_null($className)|| $className===''){
            throw new InvalidArgumentException('O nome da classe não pode ser uma string vazia ou nula.');
        }
        $this->className = $className;
        $this->reflection = new ReflectionClass($className);
    }
    /**
     * 
     * @return \ReflectionClass
     */
    public function getReflection() {
        return $this->reflection;
    }


}
