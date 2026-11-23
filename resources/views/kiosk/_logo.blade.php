{{-- Logo dari Pengaturan (SPEC v1.3 REQ-24); tanpa logo → kotak biru "OSIS". $size: tinggi dalam vh. --}}
<div class="logo" data-testid="kiosk-logo" :class="election?.logo_url && 'has-image'" style="{{ isset($size) ? "width:{$size}vh;height:{$size}vh" : '' }}">
    <img x-show="election?.logo_url" :src="election?.logo_url" alt="Logo sekolah" draggable="false">
    <span x-show="!election?.logo_url">OSIS</span>
</div>
