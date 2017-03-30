<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace EntitiesPHP\Database;

interface IRepository {
    /**
     * @param object $o The instance to make managed and persistent.
     */
    public function add(\Object $o): int;

    public function clear();

    public <T extends Object> T get(T t);

    public <T extends Object> T get(Class<T> type, Object o);

    public <T extends Object> List<T> get(String string);

    public <T extends Object> List<T> get(String string, Object[] os);

    public <T extends Object> List<T> get(String string, int i, int i1);

    public <T extends Object> List<T> get(String string, int i, int i1, Object[] os);

    public void persistAll();

    public void remove(Object o);

    public int set(String string, Object[] os);

    public long size(String string);

    public long size(String string, Object[] os);
}
