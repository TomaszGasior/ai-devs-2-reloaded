<?php

include __DIR__ . '/../vendor/autoload.php';

use AidevsTaskApi\Task;
use Symfony\Component\HttpClient\HttpClient;

$task = new Task('md2html');

$input = $task->getParam('input');
$markdown = convert_text_to_markdown($input);

$task->sendAnswer($markdown);

function convert_text_to_markdown(string $text): string
{
    $client = HttpClient::createForBaseUri('https://api.openai.com/v1/');

    $response = $client->request('POST', 'chat/completions', [
        'auth_bearer' => OPENAI_API_KEY,
        'json' => [
            'model' => $_ENV['MD2HTML_OPENAI_MODEL_NAME'],
            'max_tokens' => 1000,
            'messages' => [
                ['role' => 'system', 'content' => 'convert text to Markdown'],
                ['role' => 'user', 'content' => $text],
            ],
        ],
    ]);

    $response = $response->toArray();
    $data = $response['choices'][0]['message']['content'];

    return $data;
}


/*

fine tune base model:
gpt-3.5-turbo-0125

fine tune data:
```
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"**pogrubienie**"},{"role":"assistant","content":"<span class=\"bold\">pogrubienie</span>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"*kursywa*"},{"role":"assistant","content":"<em>kursywa</em>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"1. Element **listy**\n2. Kolejny element"},{"role":"assistant","content":"<ol>\n<li>Element <span class=\"bold\">listy</span></li>\n<li>Kolejny element</li>\n</ol>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"# Dlaczego _warto_ **zapisać się** na szkolenie?"},{"role":"assistant","content":"<h1>Dlaczego <u>warto</u> <span class=\"bold\">zapisać się</span> na szkolenie?</h1>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"# Dlaczego *warto* **zapisać się** na szkolenie?"},{"role":"assistant","content":"<h1>Dlaczego <em>warto</em> <span class=\"bold\">zapisać się</span> na szkolenie?</h1>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"[AI Devs 3.0](https://aidevs.pl)"},{"role":"assistant","content":"<a href=\"https://aidevs.pl\">AI Devs 3.0</a>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"_podkreślenie_"},{"role":"assistant","content":"<u>podkreślenie</u>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"1. Element listy\n2. Kolejny element"},{"role":"assistant","content":"<ol>\n<li>Element listy</li>\n<li>Kolejny element</li>\n</ol>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"# Bardzo _ważny_ tekst"},{"role":"assistant","content":"<h1> Bardzo <u>ważny</u> tekst</h1>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"# Bardzo *ważny* tekst"},{"role":"assistant","content":"<h1> Bardzo <em>ważny</em> tekst</h1>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"# Bardzo **ważny** tekst"},{"role":"assistant","content":"<h1> Bardzo <span class=\"bold\">ważny</span> tekst</h1>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"1. Element listy\n2. **Kolejny** element"},{"role":"assistant","content":"<ol>\n<li>Element listy</li>\n<li><span class=\"bold\">Kolejny</span> element</li>\n</ol>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"1. Element listy\n2. *Kolejny* element"},{"role":"assistant","content":"<ol>\n<li>Element listy</li>\n<li><em>Kolejny</em> element</li>\n</ol>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"1. Element listy\n2. _Kolejny_ element"},{"role":"assistant","content":"<ol>\n<li>Element listy</li>\n<li><u>Kolejny</u> element</li>\n</ol>"}]}
{"messages":[{"role":"system","content":"convert text to Markdown"},{"role":"user","content":"1. Czy lubi **placki**\n2. *Lubię* **placki** _smażone panierowane_\n3. *On* _lubi_ placki"},{"role":"assistant","content":"<ol>\n<li>Czy lubi <span class=\"bold\">placki</span></li>\n<li><em>Lubię</em> <span class=\"bold\">placki</span> <u>smażone panierowane</u></li>\n<li><em>On</em> <u>lubi</u> placki</li>\n</ol>"}]}
```

*/
