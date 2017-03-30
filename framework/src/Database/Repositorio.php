<?php

/**
 * EntitiesPHP Framework
 *
 * Aplicação framework desenvolvida para utilizar o padrão Naked Objects
 *
 * @author		Alcides Bezerra <alcidesbezerralima@gmail.com>
 * @copyright           GPL © 2015, Alcides Bezerra.
 * @license		MIT
 * @link		https://github.com/cidinho/EntitiesPHP
 * @since		Version 1.0
 * @filesource
 */

namespace EntitiesPHP\Database;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use IntlException;
use ReflectionClass;
use stdClass;

/**
 * Framework Classe Repositorio
 *
 * Essa classe controla todo o modelo de persistência baseado no ORM do 
 * Doctrine2
 *
 * @package		Framework
 * @category            Repositorio
 * @author		Alcides Bezerra <alcidesbezerralima@gmail.com>
 * @access              public
 * @version             1.0
 */
class Repositorio {

    /**
     * Caminho para as entidades do modelo de persistência
     */
    private static $entidades = array('Dominio');

    /**
     * Modo de Desenvolvimento
     * Padrão: FALSE
     * Caso seja FALSE ele não permite o modo de criação ou alteração do 
     * banco de dados, por esse motivo a constante deve ser alterada para FALSE
     * quando o desenvolvimento estiver concluído e assim evitar a modificação
     * indevida.
     * 
     * @var Boolean 
     */
    const IS_DEV_MOD = \TRUE;

    /**
     * configurações de conexão. Coloque aqui os seus dados
     */
    private static $dbParams = array(
        'driver' => 'pdo_mysql',
        'path' => __DIR__ . '/db.mysql',
        'user' => 'root',
        'password' => '',
        'dbname' => 'xml',
        'charset' => 'utf8',
    );

    /**
     * Entity Manager
     * @var EntityManager 
     */
    private static $_entityManager = null;

    /**
     * Padrão Singleton + Factory
     */
    private function __construct() {
        
    }

    /**
     * Retorna \Doctrine\ORM\EntityManager
     * 
     * @return EntityManager
     */
    public static function get_instance($entidades,$is_dev_mod,$dbparams) {

        if (self::$_entityManager === null) {
            //setando as configurações definidas anteriormente
            $config = Setup::createAnnotationMetadataConfiguration(self::$entidades, self::IS_DEV_MOD);
            $config->addCustomStringFunction('group_concat', 'Oro\ORM\Query\AST\Functions\String\GroupConcat');
            $config->addCustomNumericFunction('hour', 'Oro\ORM\Query\AST\Functions\SimpleFunction');
            $config->addCustomNumericFunction('timestampdiff', 'Oro\ORM\Query\AST\Functions\Numeric\TimestampDiff');
            $config->addCustomDatetimeFunction('date', 'Oro\ORM\Query\AST\Functions\SimpleFunction');
            //criando o Entity Manager com base nas configurações de dev e banco de dados
            self::$_entityManager = EntityManager::create(self::$dbParams, $config);
        }

        return Repositorio::$_entityManager;
    }

    /**
     * Método estático de persistência
     * 
     * @param Object $entidade
     * @return boolean
     */
    public static function salvar($entidade) {
        $em = Repositorio::get_instance();
        $em->persist($entidade);
        $em->flush();
        return true;
    }

    /**
     * Carregar um registro da entidade
     * 
     * @throws IntlException
     */
    public static function carregar() {
        throw IntlException;
    }

    /**
     * Excluir o registro da entidade
     * 
     * @param Object $entidade
     * @throws IntlException
     */
    public static function excluir($entidade) {
        $em = self::get_instance();
        $em->remove($entidade);
        $em->flush();
    }

    public static function criarSchema($classe) {
        $em = self::get_instance();
        $tool = new SchemaTool($em);
        $classes = array(
            $em->getClassMetadata($classe)
        );
        $tool->createSchema($classes);
    }

    public static function getAll($entidade) {
        $em = self::get_instance();
        $retorno = $em->getRepository($entidade)->findAll();
        $em->flush();
        return $retorno;
    }

    public static function getAllToJSON($entidade, $order = null, $limit = null) {
        $em = self::get_instance();
        if ($limit) {
            $list = $em->getRepository($entidade)->findby(array(), $order, $limit, 0);
        } else {
            $list = $em->getRepository($entidade)->findby(array(), $order);
        }
//        $qb = $em->createQueryBuilder()->getQuery();
//        $s = $qb->getSQL();
//        die($s);
        $retorno = array();
        foreach ($list as $k => $obj) {
            $retorno[$k] = new stdClass();
            foreach (get_class_methods($obj) as $method) {
                if (strpos($method, 'get') === 0) {
                    $field = strtolower(str_replace('get', '', $method));
                    $retorno[$k]->$field = call_user_func(array($obj, $method));
                }
            }
        }
        $em->flush();
        return $retorno;
    }

    public static function getRepositorio($entidade) {
        $em = self::get_instance();
        return $em->getRepository($entidade);
    }

    public static function load($entidade, $parametros) {
        return self::getRepositorio($entidade)->findOneBy($parametros);
    }

    public static function find($entidade, $idValue) {
        return self::get_instance()->find($entidade, $idValue);
    }

    public static function loadAssocList($entidade, $key) {
        $em = self::get_instance();

        $list = $em->getRepository($entidade)->findAll();
        $newList = array();
        foreach ($list as $obj) {
            if (property_exists($obj, $key)) {
                $newIndice = call_user_func(array($obj, 'get' . ucfirst($key)));
                $newList[$newIndice] = $obj;
            } else {
                show_error('Atributo não encontrado no objeto selecionado', 500, __CLASS__);
            }
        }
        return $newList;
    }

    public static function getTodasAsEntidades() {
        $entities = array();
        $em = self::get_instance();
        $meta = $em->getMetadataFactory()->getAllMetadata();
        foreach ($meta as $m) {
            $entities[] = $m->getName();
        }
        return $entities;
    }

    public static function atualizarSchema() {

        $em = self::get_instance();
        $tool = new SchemaTool($em);
        $entidades = self::getTodasAsEntidades();
        $classes = array();
        foreach ($entidades as $entidade) {
            $classes [] = $em->getClassMetadata($entidade);
            //Rotas
            self::cadastrarRotas($entidade);
        }

        $tool->updateSchema($classes, 'force');
    }

    public static function savarPost($entidade, $data) {
        foreach ($data as $campo => $valor) {
            call_user_func(array($entidade, 'set' . ucfirst($campo)), $valor);
        }
        self::salvar($entidade);
    }

    public static function excluirEmLote($entidade, $lote, $chave) {
        foreach ($lote as $campo) {
            $entidade = self::load($entidade, array($chave => $campo));
            self::excluir($entidade);
        }
        return true;
    }

    public static function getAtributos($className) {
        $em = self::get_instance();
        $entidade = $em->getClassMetadata($className);
        return $entidade->fieldNames;
    }

    public static function getAnotacao($className, $parametro) {
        $entidade = new ReflectionClass($className);
        $annotations = str_replace("*", "", $entidade->getDocComment());

        //Arrays utilizados
        $parametros = $match_names = $match_values = array();

        preg_match_all("/\@(.*)\(/", $annotations, $match_names);

        if (count($match_names) > 1) {
            foreach ($match_names[1] as $key => $tag) {
                $annotationName = trim($tag);
                preg_match_all("/\((\s*.*\s*)\)/", $annotations, $match_values);
                $parametros[$annotationName] = self::_getAnotacao($match_values, $key);
            }
            $retorno = (array_key_exists($parametro, $parametros)) ? $parametros[$parametro] : null;

            return is_object($retorno) ? get_object_vars($retorno) : $retorno;
        }
        return null;
    }

    private static function _getAnotacao($match_values, $key) {
//        Debug::dump($match_values);
        if (count($match_values) > 1 && $values = json_decode($match_values[1][$key])) {
            return $values;
        }
        return null;
    }

    public static function cadastrarRotas($entidade) {
        $modulo = $controlador = $acao = '';
        $rotas = self::getAnotacao($entidade, 'Rotas');
//        Debug::dump($rotas);
        if (is_array($rotas)) {
            foreach ($rotas as $uri => $objUri) {
                $uri_segment = explode("/", $uri);

                extract(Uri::desmembrarUriSegment($uri_segment));

                $displayUriBusca = Uri::getDisplayNameUri($uri_segment);

                if ($displayUriBusca == end($uri_segment)) {
                    $obj = new Uri($modulo, $controlador, $acao, '', (isset($objUri->displayUri)) ? $objUri->displayUri : '', (isset($objUri->icon)) ? $objUri->icon : '', $uri, 1);
                    self::salvar($obj);
                }
                unset($displayUriBusca);

                self::setLinkMenu($objUri, $uri);
            }
        }
    }

}
