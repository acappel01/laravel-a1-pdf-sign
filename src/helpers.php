<?php

use LSNepomuceno\LaravelA1PdfSign\{ManageCert, SignaturePdf, ValidatePdfSignature};
use Illuminate\Support\{Str, Facades\File, Fluent};
use Illuminate\Http\UploadedFile;

if (!function_exists('signPdf')) {
  /**
   * signPdf - Helper to fast signature pdf from pfx file
   */
  function signPdfFromFile(string $pfxPath, string $password, string $pdfPath, string $mode = SignaturePdf::MODE_RESOURCE)
  {
    try {
      return (new SignaturePdf(
        $pdfPath,
        (new ManageCert)->fromPfx($pfxPath, $password),
        $mode
      ))->signature();
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}

if (!function_exists('signPdfFromUpload')) {
  /**
   * signPdfFromUpload - Helper to fast signature pdf from uploaded certificate
   */
  function signPdfFromUpload(UploadedFile $uploadedPfx, string $password, string $pdfPath, string $mode = SignaturePdf::MODE_RESOURCE)
  {
    try {
      return (new SignaturePdf(
        $pdfPath,
        (new ManageCert)->fromUpload($uploadedPfx, $password),
        $mode
      ))->signature();
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}

if (!function_exists('encryptCertData')) {
  /**
   * encryptCertData - Helper to fast encrypt certificate data
   * @param \Illuminate\Http\UploadedFile|string $uploadedOrPfxPath
   */
  function encryptCertData($uploadedOrPfxPath, string $password): Fluent
  {
    try {
      $cert = new ManageCert;

      if ($cert instanceof UploadedFile) {
        $cert->fromUpload($uploadedOrPfxPath, $password);
      } else {
        $cert->fromPfx($uploadedOrPfxPath, $password);
      }

      return new Fluent([
        'certificate' => $cert->getEncrypter()->encryptString($cert->getCert()->original),
        'password'    => $cert->getEncrypter()->encryptString($password),
        'hash'        => $cert->getHashKey(), // IMPORTANT, USE ON DECRYPT HELPER
      ]);
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}

if (!function_exists('decryptCertData')) {
  /**
   * decryptCertData - Helper to fast decrypt certificate
   */
  function decryptCertData(string $hashKey, string $encryptCert, string $password)
  {
    try {
      $cert    = (new ManageCert)->setHashKey($hashKey);
      $uuid    = Str::orderedUuid();
      $pfxName = "{$cert->getTempDir()}{$uuid}.pfx";

      File::put($pfxName, $cert->getEncrypter()->decryptString($encryptCert));

      return $cert->fromPfx(
        $pfxName,
        $cert->getEncrypter()->decryptString($password)
      );
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}

if (!function_exists('a1TempDir')) {
  /**
   * a1TempDir - Helper to make temp dir and files
   */
  function a1TempDir(bool $tempFile = false, string $fileExt = '.pfx')
  {
    $tempDir = __DIR__ . '/Temp/';

    if ($tempFile) $tempDir .= Str::orderedUuid() . $fileExt;

    return $tempDir;
  }
}

if (!function_exists('validatePdfSignature')) {
  /**
   * validatePdfSignature - Validate pdf signature
   */
  function validatePdfSignature(string $pdfPath): Fluent
  {
    try {
      return ValidatePdfSignature::from($pdfPath);
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}
