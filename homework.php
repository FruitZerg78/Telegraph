<?php


interface LoggerInterface
{
    public function logMessage($message);
    public function lastMessage($offset);
}

interface EventListenerInterface
{
    public function attachEvent();
    public function detouchEvent();
}

class TelegraphText
{
    private string $title = '';
    private string $text = '';
    private string $author;
    private string $published;
    private string $slug;

    public function __construct(string $author,string $title)
    {
        $this->author = $author;
        $this->title = $title;
        $this->published = date('d.m.Y h:i:s');
    }

    public function __set(string $name, $value) : void
    {
        switch ($name)
        {
            case 'author':
                if( strlen($value) > 120 ){
                    echo "Превышено количество символов в строке" . PHP_EOL;
                    return;
                }
                $this->author = $value;
                break;

            case 'slug':
                if( preg_match("#^[a-zA-Z0-9_\.]*$#", $value) == 0 ){
                    echo "Поле содержит недопустимые символы" . PHP_EOL;
                    break;
                }
                $this->slug = $value;
                break;

            case 'published':
                    if( $value < date('d.m.Y h:i:s') ){
                        echo "Некорректное значение времени" . PHP_EOL;
                        return;
                    }
                    $this->published = $value;
                    break;
            case 'text':
                $this->text = $value;
                $this->storeText();
                break;
        }
    }

    public function __get(string $name)
    {
        switch ($name)
        {
            case 'author':
                return $this->author;
            case 'slug':
                return $this->slug;
            case 'published':
                return $this->published;
            case 'text':
                return $this->loadText();
        }
    }

    private function storeText()
    {
        $store = [
            'text' => $this->text,
            'title' => $this->title,
            'author' => $this->author,
            'published' => $this->published
        ];
        $serializedData = serialize($store);
        file_put_contents($this->slug, $serializedData);
    }

    private function loadText()
    {
        if (file_get_contents($this->slug)) {
            $deserializedData = unserialize(file_get_contents($this->slug));
            $this->title = $deserializedData['title'];
            $this->text = $deserializedData['text'];
            $this->author = $deserializedData['author'];
            $this->published = $deserializedData['published'];
            return $deserializedData['text'];
        } else {
            echo 'Файл пустой' . PHP_EOL;
        }
    }

    public function editText(string $newText, string $newTitle)
    {
        $this->text = $newText;
        $this->title = $newTitle;
    }
}



abstract class Storage implements LoggerInterface, EventListenerInterface
{
    public string $message;
    public array $log;

   public function logMessage($message)
   {
       // TODO: Implement logMessage() method.
   }

   public function lastMessage($offset)
   {
       // TODO: Implement lastMessage() method.
   }

    public function attachEvent()
    {
        // TODO: Implement attachEvent() method.
    }

    public function detouchEvent()
    {
        // TODO: Implement detouchEvent() method.
    }

    abstract function create(object $telegraphText);

    abstract function read(int|string $slug) ;

    abstract function update(int|string $id, $slug, $newObject);

    abstract function delete(int|string $id, $slug);

    abstract function list(string $path);
}

abstract class View
{
    public $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    abstract function displayTextById(int|string $id);

    abstract function displayTextByIrl($url);
}

abstract class User implements EventListenerInterface
{
    protected int|string $id;
    protected string $name;
    protected string $role;

    protected abstract function getTextsToEdit();

    public function attachEvent()
    {
        // TODO: Implement attachEvent() method.
    }

    public function detouchEvent()
    {
        // TODO: Implement detouchEvent() method.
    }
}



class FileStorage extends Storage
{
    public function create(object $telegraphText)
    {
        $fileName = $telegraphText->slug . '_' . date('d.m.y_h:i:s') . '.txt';
        $i = 1;
        while(file_exists($fileName)){
            $fileName = $telegraphText->slug . '_' . date('d.m.y h:i:s') . '_' . $i . '.txt';
            $i++;
        }
        $telegraphText->slug = $fileName;
        $serialize = serialize($telegraphText);
        file_put_contents($fileName, $serialize);
        return $telegraphText->slug;
    }


    public function read(int|string $slug) : string
    {
        if( file_exists($slug) ){
            return unserialize( file_get_contents($slug) );
        } else {
            return "Файл не существует" . PHP_EOL;
        }
    }

    public function update(int|string $id, $slug, $newObject)
    {
        $serialized = serialize($newObject);
        file_put_contents($slug, $serialized);
    }

    public function delete(int|string $id, $slug)
    {
        if ( file_exists($slug) ){
            unlink($slug);
        } else {
            return 'Файл не найден';
        }
    }

    public function list(string $path)
    {
        $list = [];
        foreach ( scandir($path) as $files ){
            if ($files == '.' || $files == '..') {
                continue;
            }
            if( is_readable($path . DIRECTORY_SEPARATOR . $files) ){
                $list[] = unserialize(file_get_contents($path . DIRECTORY_SEPARATOR . $files));
                return $list;
            }
        }
    }
}

$telegraphText = new TelegraphText('Roman', 'new');
$telegraphText->slug = __DIR__ . DIRECTORY_SEPARATOR . '@' . 'test';
$telegraphText->text = 'a brand new text';
echo $telegraphText->slug;
echo $telegraphText->text;

