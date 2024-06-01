<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('meme');

$client = HttpClient::createForBaseUri('https://get.renderform.io/api/v2/');

$response = $client->request('POST', 'render', [
    'headers' => [
        'X-API-KEY' => $_ENV['RENDERFORM_API_KEY'],
    ],
    'json' => [
        'template' => $_ENV['RENDERFORM_MEME_TEMPLATE_ID'],
        'data' => [
            'image' => $task->getParam('image'),
            'text' => $task->getParam('text'),
        ],
    ],
]);

$response = $response->toArray();

$task->sendAnswer($response['href']);

/*

https://renderform.io/console/template/html-editor/

HTML
```
<div class="wrap">
  <img src="{{image}}" class="image">
  <div class="text">{{text}}</div>
</div>
```

CSS
```
body {
  background: #000;
}

.wrap {
  margin: 50px;
  height: calc(100% - 100px);
  display: grid;
  grid-template-rows: auto min-content;
  gap: 30px 0;
}

.image {
  height: 100%;
  width: 100%;
  object-fit: contain;
  object-position: center;
}

.text {
  text-align: center;
  color: #fff;
  font-family: "Arial";
  font-size: 40px;
  line-height: 1.5;
}
```

Sample Data
```
{
  "text": "Lubię placki smażone, ponieważ takie są zawsze najlepsze. Zdecydowanie niewiarygodne. Musisz spróbować sam, polecam.",
  "image": "https://picsum.photos/536/354"
}
```

Options
Width: 1080
Height: 1080
Format: JPG

*/
