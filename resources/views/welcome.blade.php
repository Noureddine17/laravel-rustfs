<html>

<head>
    <meta charset="UTF-8">
    <title>Upload Rustfs</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

<div class="text-center mb-10">
    <h1 class="text-4xl font-bold text-heading">
        Image Uploader
    </h1>
    <p class="text-body mt-2 text-sm">
        Taille maximale autorisée : <span class="font-medium">200 MB</span>
    </p>
</div>

<form action="{{ route('upload.submit') }}" method="POST" enctype="multipart/form-data" class="mt-10 p-6 border border-gray-300 bg-white rounded-lg shadow">
    @csrf

    {{-- Message d’erreur --}}
    @if ($errors->any())
        <div class="mb-4 text-red-700 bg-red-100 border border-red-300 rounded-md px-4 py-2 text-sm font-medium">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Message de succès --}}
    @if (session('success'))
        <div class="mb-4 text-green-700 bg-green-100 border border-green-300 rounded-md px-4 py-2 text-sm font-medium">
            {{ session('success') }} ({{ session('uploadedName') }})
        </div>
    @endif

    {{-- Input fichier --}}
    <label class="block mb-4">
        <span class="text-gray-700 font-medium">Choisir un fichier :</span>
        <input 
            type="file" 
            name="file"
            class="block mt-2 w-full border border-gray-400 p-2 rounded bg-white text-black"
        >
    </label>

    {{-- Bouton Upload --}}
    <button 
        type="submit"
        class="block w-full text-center text-white font-semibold rounded px-6 py-3"
        style="background:#2563eb !important; display:block !important;">
        UPLOAD
    </button>
</form>

@if (!empty($files))
    <div class="relative overflow-x-auto bg-neutral-primary-soft shadow-xs rounded-base border border-default mt-10">
        <table class="w-full text-sm text-left rtl:text-right text-body">
            <thead class="text-sm text-body bg-neutral-secondary-soft border-b border-default">
                <tr>
                    <th scope="col" class="px-6 py-3 font-medium">
                        File Name
                    </th>
                    <th scope="col" class="px-6 py-3 font-medium">
                        View
                    </th>
                    <th scope="col" class="px-6 py-3 font-medium">
                        Download
                    </th>
                    <th scope="col" class="px-6 py-3 font-medium">
                        Delete
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($files as $file)
                    @php
                        $originalName = basename($file);
                        $url = Storage::disk('rustfs')->url($file);
                    @endphp

                    <tr class="border-b border-default">
                        <td class="px-6 py-4 font-medium text-heading whitespace-nowrap">
                            {{ $originalName }}
                        </td>

                        <td class="px-6 py-4">
                            <a href="{{ route('file.preview', ['file' => $originalName]) }}" target="_blank" class="text-green-600 hover:underline">
                                Visualiser
                            </a>
                        </td>

                        <td class="px-6 py-4">
                            <a href="{{ $url }}" target="_blank" class="text-blue-600 hover:underline">
                                Télécharger
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <form action="{{ route('file.delete', ['file' => $originalName]) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?');">
                                @csrf
                                <button type="submit" class="text-red-600 hover:underline">
                                    Supprimer
                                </button>
                            </form>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center">Aucun fichier trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@else
    <p class="text-center">Aucun fichier trouvé.</p>
@endif
</body>

</html>
