<div class="pagetitle">
    <h1>Diagnosis</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item active">Diagnosis</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <!-- PathLab -->
        <div class="col-xxl-2 col-md-4 col-sm-6 mb-4">
            <div class="card">
                <a href="javascript:load_form('<?= base_url('diagnosis/pathology') ?>','Pathology');" class="text-decoration-none">
                    <div class="card-body text-center" style="min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="mb-3">
                            <i class="bi bi-eyedropper" style="font-size: 3rem; color: #4154f1;"></i>
                        </div>
                        <h5 class="card-title mb-0">PathLab</h5>
                    </div>
                </a>
            </div>
        </div>

        <!-- Biopsy -->
        <div class="col-xxl-2 col-md-4 col-sm-6 mb-4">
            <div class="card">
                <a href="javascript:load_form('<?= base_url('diagnosis/biopsy') ?>','Biopsy');" class="text-decoration-none">
                    <div class="card-body text-center" style="min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="mb-3">
                            <i class="bi bi-capsule" style="font-size: 3rem; color: #2eca6a;"></i>
                        </div>
                        <h5 class="card-title mb-0">Biopsy</h5>
                    </div>
                </a>
            </div>
        </div>

        <!-- MRI -->
        <div class="col-xxl-2 col-md-4 col-sm-6 mb-4">
            <div class="card">
                <a href="javascript:load_form('<?= base_url('diagnosis/mri') ?>','MRI');" class="text-decoration-none">
                    <div class="card-body text-center" style="min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="mb-3">
                            <i class="bi bi-circle-square" style="font-size: 3rem; color: #ff771d;"></i>
                        </div>
                        <h5 class="card-title mb-0">MRI</h5>
                    </div>
                </a>
            </div>
        </div>

        <!-- X-Ray -->
        <div class="col-xxl-2 col-md-4 col-sm-6 mb-4">
            <div class="card">
                <a href="javascript:load_form('<?= base_url('diagnosis/xray') ?>','X-Ray');" class="text-decoration-none">
                    <div class="card-body text-center" style="min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="mb-3">
                            <i class="bi bi-file-medical" style="font-size: 3rem; color: #012970;"></i>
                        </div>
                        <h5 class="card-title mb-0">X-Ray</h5>
                    </div>
                </a>
            </div>
        </div>

        <!-- CT-Scan -->
        <div class="col-xxl-2 col-md-4 col-sm-6 mb-4">
            <div class="card">
                <a href="javascript:load_form('<?= base_url('diagnosis/ctscan') ?>','CT-Scan');" class="text-decoration-none">
                    <div class="card-body text-center" style="min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="mb-3">
                            <i class="bi bi-disc" style="font-size: 3rem; color: #bb0852;"></i>
                        </div>
                        <h5 class="card-title mb-0">CT-Scan</h5>
                    </div>
                </a>
            </div>
        </div>

        <!-- Ultra Sound -->
        <div class="col-xxl-2 col-md-4 col-sm-6 mb-4">
            <div class="card">
                <a href="javascript:load_form('<?= base_url('diagnosis/ultrasound') ?>','Ultra Sound');" class="text-decoration-none">
                    <div class="card-body text-center" style="min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="mb-3">
                            <i class="bi bi-soundwave" style="font-size: 3rem; color: #4154f1;"></i>
                        </div>
                        <h5 class="card-title mb-0">Ultra Sound</h5>
                    </div>
                </a>
            </div>
        </div>

        <!-- Echo -->
        <div class="col-xxl-2 col-md-4 col-sm-6 mb-4">
            <div class="card">
                <a href="javascript:load_form('<?= base_url('diagnosis/echo') ?>','Echo');" class="text-decoration-none">
                    <div class="card-body text-center" style="min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="mb-3">
                            <i class="bi bi-heart-pulse" style="font-size: 3rem; color: #0ea5e9;"></i>
                        </div>
                        <h5 class="card-title mb-0">Echo</h5>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>
