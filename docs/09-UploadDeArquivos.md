# Upload de Arquivos

Arquivos `$_FILES` foram totalmente remodelados e são recebidos como Instâncias super seguras Orientadas a Objetos: `UploadedFile`.

Para validar nas Models:
```php
// Aceita qualquer Binário de no máx 10 MB.
#[File(maxSize: 10485760)] 

// Exige Especificamente que a Imagem passe num funil severo para barrar uploads perigosos mascarados. Max 2MB, jpg e png.
#[Image(maxSizeMb: 2, mimes: ['image/jpeg', 'image/png'], message: "A CNH anexada não bate com nada do que fomos configurados pra aceitar!")]
public ?\Core\Http\UploadedFile $arquivocnh = null;
```

Mova este arquivo validado do Local Temporário direto para onde quiser dentro do Controller de Resposta:
```php
public function store(Request $request) {
    if ($request->hasFile('foto')) {
        $arquivo = $request->getFile('foto');
        $destinoFinal = __DIR__ . '/../../public/uploads/' . $arquivo->getClientFilename();
        
        $arquivo->moveTo($destinoFinal);
    }
}
```
