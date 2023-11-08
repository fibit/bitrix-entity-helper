<?php
/**
 * Entities helper class for Bitrix Framework
 * 
 * @link https://github.com/fibit/bitrix-entity-helper
 *
 * @author Pavel Romanov <pr@kolibri.pw>
 * @license MIT
 * @version 1.0.0
 */

namespace Fibit;

use \Bitrix\Main\Loader,
    \Bitrix\Main\Application,
    \Bitrix\Main\Diag\Helper,
    \Bitrix\Main\Entity\Query,
    \Bitrix\Main\UI\PageNavigation,
    \Bitrix\Iblock\Iblock,
    \Bitrix\Highloadblock\HighloadBlockTable;

class EntityHelper
{
  /**
   * Creation and initialization of a class for the standard infoblock
   *
   * @param int $entity
   * @return string
   */
  private static function getIClass(int $entity): string
  {
    if (!Loader::IncludeModule("iblock"))
      throw new Exception("Include module `iblock` is failed.");

    return (string)Iblock::wakeUp($entity)->getEntityDataClass();
  }

  /**
   * Assembly and initialization of a class for the highload infoblock
   *
   * @param string $entity
   * @return string
   */
  private static function getHClass(string $entity): string
  {
    if (!Loader::IncludeModule("highloadblock"))
      throw new Exception("Include module `highloadblock` is failed.");

    return (string)HighloadBlockTable::compileEntity($entity)->getDataClass();
  }

  /**
   * Assembling and initializing a class for the system entity
   *
   * @param object $entity
   * @return string
   */
  private static function getTClass(object $entity): string
  {
    return (string)$entity::getEntity()->getDataClass();
  }

  /**
   * Preparing the class for execution
   *
   * @param int|string|object $entity
   * @return string
   */
  public static function getClass(int|string|object $entity): string
  {
    if (filter_var($entity, FILTER_VALIDATE_INT) !== false)
      return self::getIClass($entity);
    elseif (is_string($entity))
      return self::getHClass($entity);
    elseif (is_object($entity))
      return self::getTClass($entity);
  }

  /**
   * Generating an SQL-query without execution
   *
   * @param int|string|object $entity
   * @param array $params
   * @param string $alias
   * @return string
   */
  public static function getQuery(int|string|object $entity, array $params = [], string $alias = ""): string
  {
    $query = new Query(self::getClass($entity));

    if ($alias)
      $query->setCustomBaseTableAlias($alias);

    if ($params["select"])
      $query->setSelect($params["select"]);

    if ($params["filter"])
      $query->setFilter($params["filter"]);

    if ($params["order"])
      $query->setOrder($params["order"]);

    if ($params["group"])
      $query->setGroup($params["group"]);

    if ($params["offset"])
      $query->setOffset($params["offset"]);

    if ($params["limit"])
      $query->setLimit($params["limit"]);

    if ($params["runtime"])
      foreach ($params["runtime"] as $runtime)
        $query->registerRuntimeField($runtime);

    return $query->getQuery();
  }

  /**
   * Getting a list of elements from entity
   *
   * @param int|string|object $entity
   * @param array $params
   * @return array
   */
  public static function getRows(int|string|object $entity, array $params = []): array
  {
    if ($params["count_total"]) {
      $nav = new PageNavigation(hash("crc32b", (string)$entity.serialize($params)));
      $nav->setPageSize($params["limit"] ?? 20)->initFromUri();

      $params["offset"] = $nav->getOffset();
      $params["limit"] = $nav->getLimit();
    }

    $start = Helper::getCurrentMicrotime();
    $response = self::getClass($entity)::getList($params);
    $finish = Helper::getCurrentMicrotime();

    if ($params["count_total"])
      $nav->setRecordCount($response->getCount());

    return array(
      "result" => $response->fetchAll(),
      "count" => ($params["count_total"]) ? $response->getCount() : $response->getSelectedRowsCount(),
      "nav" => $nav,
      "time" => $finish - $start
    );
  }

  /**
   * Getting one element from entity
   *
   * @param int|string|object $entity
   * @param array $params
   * @return array
   */
  public static function getRow(int|string|object $entity, array $params = []): array
  {
    $start = Helper::getCurrentMicrotime();
    $response = self::getClass($entity)::getList($params);
    $finish = Helper::getCurrentMicrotime();

    return array(
      "result" => $response->fetch(),
      "time" => $finish - $start
    );
  }

  /**
   * Adding an element to an entity
   *
   * @param int|string|object $entity
   * @param array $fields
   * @return array
   */
  public static function addRow(int|string|object $entity, array $fields): array
  {
    Application::getConnection()->startTransaction();

    $start = Helper::getCurrentMicrotime();
    $response = self::getClass($entity)::add($fields);
    $finish = Helper::getCurrentMicrotime();

    if ($response->isSuccess()) {
      Application::getConnection()->commitTransaction();

      $result = array(
        "result" => $response->getId(),
        "time" => $finish - $start
      );
    } else {
      Application::getConnection()->rollbackTransaction();

      $result = array(
        "error" => true
      );
    }

    return $result;
  }

  /**
   * Updating an element in an entity
   *
   * @param int|string|object $entity
   * @param int $id
   * @param array $fields
   * @return array
   */
  public static function updRow(int|string|object $entity, int $id, array $fields): array
  {
    Application::getConnection()->startTransaction();

    $start = Helper::getCurrentMicrotime();
    $response = self::getClass($entity)::update($id, $fields);
    $finish = Helper::getCurrentMicrotime();

    if ($response->isSuccess()) {
      Application::getConnection()->commitTransaction();

      $result = array(
        "result" => $response->getId(),
        "time" => $finish - $start
      );
    } else {
      Application::getConnection()->rollbackTransaction();

      $result = array(
        "error" => true
      );
    }

    return $result;
  }

  /**
   * Removing an element from an entity
   *
   * @param int|string|object $entity
   * @param int $id
   * @return array
   */
  public static function delRow(int|string|object $entity, int $id): array
  {
    Application::getConnection()->startTransaction();

    $start = Helper::getCurrentMicrotime();
    $response = self::getClass($entity)::delete($id);
    $finish = Helper::getCurrentMicrotime();

    if ($response->isSuccess()) {
      Application::getConnection()->commitTransaction();

      $result = array(
        "result" => true,
        "time" => $finish - $start
      );
    } else {
      Application::getConnection()->rollbackTransaction();

      $result = array(
        "error" => true
      );
    }

    return $result;
  }
}
