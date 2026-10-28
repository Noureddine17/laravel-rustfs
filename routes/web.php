<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

Route::get("/", function () {
    // Récupérer la liste des fichiers dans le dossier "uploads" depuis RustFS
    $files = Storage::disk("rustfs")->files("uploads");

    // Passer les fichiers à la vue
    return view("welcome", compact("files"));
})->name("upload.form");

Route::post("/", function (Request $request) {
    // Vérifier qu'un fichier a été uploadé
    $request->validate([
        'file' => 'required|file|max:204800', // 200mo max
    ], [
        'file.required' => 'Aucun fichier envoyé.',
        'file.max' => 'Le fichier dépasse 200 Mo.',
    ]);

    $file = $request->file('file');
    // Récupérer le vrai nom du fichier
    $originalName = $request->file('file')->getClientOriginalName();
    
    // Upload dans RustFS
    $path = Storage::disk('rustfs')->putFileAs(
        'uploads',      // dossier
        $file,          // fichier
        $originalName   // nom à utiliser dans RustFS
    );

    return back()->with([
        'success' => "Upload réussi !",
        'uploadedName' => $originalName,
        'path' => $path,
    ]);
})->name("upload.submit");

Route::post("/delete/{file}", function ($file) {
    // Supprimer le fichier dans RustFS
    Storage::disk('rustfs')->delete("uploads/{$file}");

    return back()->with('success', "Fichier supprimé !");
})->name("file.delete");

Route::get("/delete/{file}", function ($file) {
    $files = Storage::disk("rustfs")->files("uploads");
    return $files;
});

Route::get("/preview/{file}", function ($file) {
    // $file = le nom du fichier venant de l'URL, ex: "mon-image.png"
    // urldecode = convertir les caractères encodés en caractères normaux
    $file = urldecode($file);
    
    // $stream = récupérer le fichier depuis le disque rustfs en mode "stream" (lecture par parties)
    $stream = Storage::disk('rustfs')->readStream("uploads/{$file}");
    
    // $ext = récupérer l'extension du fichier et la convertir en minuscules
    // pathinfo() = fonction PHP qui extrait la partie "extension" du nom de fichier
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    // $mimeTypes = tableau qui associe chaque extension à son type MIME
    // exemple: 'png' => 'image/png' veut dire que les fichiers .png sont des images PNG
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
    ];
    
    // $mime = chercher le type MIME selon l'extension
    // ?? 'application/octet-stream' = si l'extension n'existe pas dans le tableau, utiliser ce type par défaut ( ternaire )
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

    // ============ EXPLICATION DU RETURN ============
    
    // return = retourner une réponse HTTP au navigateur
    
    // response() = fonction Laravel qui crée une "réponse HTTP"
    // Exemple simple: response('bonjour') envoie "bonjour" au navigateur
    
    // ->stream() = on va envoyer les données en "streaming" (par parties, pas tout d'un coup)
    // Utile pour les gros fichiers: on lit un morceau, on l'envoie, on lit le suivant, etc.
    // Plutôt que charger TOUT le fichier en mémoire avant de l'envoyer
    
    // fpassthru($stream) = fonction PHP qui "lit et envoie" le stream
    // - fpassthru = "file pass through" = lire le fichier et l'envoyer directement
    // - $stream = le fichier qu'on lit depuis rustfs
    // - Résultat: envoyer le contenu du fichier au navigateur
    
    // 200 = le code HTTP "OK" (succès)
    // - 200 = la requête a réussi
    // - 404 = fichier non trouvé
    // - 500 = erreur serveur
    // Le navigateur sait que tout a marché grâce à ce code
    
    // [ 'Content-Type' => $mime ] = tableau avec un header HTTP
    // - Header = information envoyée avant le contenu du fichier
    // - 'Content-Type' = dit au navigateur "voilà le type du fichier"
    // - $mime = la valeur (ex: 'image/png', 'image/jpeg', etc)
    // - Grâce à ce header, le navigateur sait si c'est une image, PDF, texte, etc.
    // - Si Content-Type = 'image/png', le navigateur AFFICHE l'image
    // - Si Content-Type = 'application/octet-stream', le navigateur TÉLÉCHARGE le fichier
    
    return response()->stream(function () use ($stream) {
        fpassthru($stream);
    }, 200, [
        'Content-Type' => $mime,
    ]);
    
// })->name("file.preview");
// - name("file.preview") = donne un NOM à cette route
// - Crée l'URL: /preview/mon-image.png
})->name("file.preview");


