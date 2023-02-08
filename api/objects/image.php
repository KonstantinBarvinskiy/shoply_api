<?php

class Image {

    // подключение к БД таблице
    private $conn;
    private $table_name = "images";// Изображения

    // свойства объекта
    public $id;        // Идентификатор  (Int)
    public $src;       // Ссылка
    public $md5;       // Хэш
    public $module;    // Модуль
    public $module_id; // Id в модуле
    public $alter_key; // Альтернативный ключ
    public $main;      // Основное (Y/N)
    public $order;     // Порядок

    public $item;
    public $width;
    public $height;
    public $file_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function mained() {
        if (imget()->StarImage($this->id)) return true;
        return false;
    }

    public function update() {
        $i = 0;
        foreach ($this->order as $id=>$ord) {
            $query = "UPDATE `$this->table_name` SET `order` = '{$ord}' WHERE `id` = '{$id}'";
            $stmt = $this->conn->prepare($query);
            if ($stmt->execute()) {
                $i++;
            }
        }
        if ($i>0) return true;
        return false;
    }

    public function delete(){
        $this_img = imget()->GetImage($this->id);
        if($this_img['module'] == "Catalog" and $this_img['module_id'] != 0 and $this_img['main'] == 'Y') {
            $mod_id = $this_img['module_id'];
        }
        if (imget()->DelImage($this->id)) $del = true;
        if (isset($mod_id)) {
            $stmt = imget()->GetImages('Catalog', $mod_id);
            if (!empty($stmt)) imget()->StarImage($stmt[0]['id']);
        }
        if (isset($del)) return true;
        return false;
    }

    public function deleteMany(){
        if (imget()->DelImages($this->module, $this->module_id)) return true;
        return false;
    }

    public function read() {
        if ($this->main == 'Y') {
            $stmt = imget()->GetMainImage($this->item['module'], $this->item['module_id']);
            if(!empty($stmt)) {
                unset($stmt['0'], $stmt['1'], $stmt['2'], $stmt['3'], $stmt['4'], $stmt['5'], $stmt['6'], $stmt['7'], $stmt['md5']);
                if (isset($this->width) or isset($this->height)) {
                    $stmt['resize'] = imget()->ResizeImage($stmt['src'], $this->width, $this->height);
                }
                return $stmt;
            }
        }
        else {
            $stmt = imget()->GetImages($this->item['module'], $this->item['module_id']);
            if (!empty($stmt)) {
                foreach ($stmt as $k => $img) {
                    unset($stmt[$k]['0'], $stmt[$k]['1'], $stmt[$k]['2'], $stmt[$k]['3'], $stmt[$k]['4'], $stmt[$k]['5'], $stmt[$k]['6'], $stmt['7'], $stmt[$k]['md5']);
                    if (isset($this->width) or isset($this->height)) {
                        $stmt[$k]['resize'] = imget()->ResizeImage($img['src'], $this->width, $this->height);
                    }
                }
                return $stmt;
            }
        }
        if(empty($stmt)) return null;
    }
}