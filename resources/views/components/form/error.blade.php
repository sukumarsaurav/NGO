@props(['name', 'id' => null])

@error($name)
    <p {{ $attributes->merge(['id' => $id ?? $name.'-error', 'class' => 'mt-2 text-sm text-danger']) }} role="alert">
        {{ $message }}
    </p>
@enderror
