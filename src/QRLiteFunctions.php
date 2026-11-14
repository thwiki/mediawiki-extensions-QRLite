<?php

namespace MediaWiki\Extension\QRLite;

// 注意：扩展自带的 vendor/endroid/qr-code 是 4.6.x，必须使用 4.x 的 setter API。
// 上游 main 分支已改成 endroid 6.x（构造参数 + ErrorCorrectionLevel 枚举），
// 直接采用会在 4.6 上抛 Class "Endroid\QrCode\ErrorCorrectionLevel" not found。

use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Exception;

/**
 * The actual QRLite Functions
 *
 * They can be used in a programmatic way through this class
 * Use the static getId function as the main entry point
 *
 * @file
 * @ingroup Extensions
 */
class QRLiteFunctions {

	/**
	 * @param array $params
	 * @return string HTML for display of the QR code.
	 */
	public static function generateQRCode( $params = [] ) {
		// MediaWiki 1.46 移除了全局 Html 别名（1.40+ 为 MediaWiki\Html\Html），这里做兼容解析。
		$htmlClass = class_exists( \MediaWiki\Html\Html::class )
			? \MediaWiki\Html\Html::class
			: \Html::class;

		// Dependency check.
		if ( !class_exists( QrCode::class ) ) {
			return $htmlClass::errorBox( 'QRLite error: QrCode class not found, you may need to run "composer install".' );
		}

		// Defaults and escaping
		$content = self::paramGet( $params, 'prefix', '___MAIN___' );

		$format = self::paramGet( $params, 'format', 'png' );

		$size = self::paramGet( $params, 'size', 6 );
		$margin = self::paramGet( $params, 'margin', 0 );

		$ecc = (int)self::paramGet( $params, 'ecc', 2 );

		// TODO: Doesn't seem to work
		$eccLevel = new ErrorCorrectionLevelMedium();
		if ( $ecc === 1 ) {
			$eccLevel = new ErrorCorrectionLevelLow();
		} else {
			if ( $ecc === 2 ) {
				$eccLevel = new ErrorCorrectionLevelMedium();
			} else {
				if ( $ecc === 3 ) {
					$eccLevel = new ErrorCorrectionLevelQuartile();
				} else {
					if ( $ecc === 4 ) {
						$eccLevel = new ErrorCorrectionLevelHigh();
					}
				}
			}
		}

		$image = '';
		try {
			$qrCode = new QrCode( $content );
			$qrCode->setSize( $size * 30 );
			$qrCode->setMargin( $margin );
			$qrCode->setErrorCorrectionLevel( $eccLevel );
			$writer = $format === 'svg' ? new SvgWriter() : new PngWriter();
			$writerOptions = [
				SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true
			];
			$result = $writer->write( $qrCode, null, null, $writerOptions );

			if ( $format === 'svg' ) {
				$image = '<span class="svg-container" title="' . $content . '">' . $result->getString() . '</span>';
			} else {
				$image = $htmlClass::element( 'img', [ 'src' => $result->getDataUri(), 'title' => $content ] );
			}
		} catch ( Exception $e ) {
			$image = '<span class="error-message">' . $e->getMessage() . '</span>';

		}

		$downloadButtons = '';
		$result = '<span class="qrlite-result">' . $image . $downloadButtons . '</span>';

		return $result;
	}

	//////////////////////////////////////////
	// HELPER FUNCTIONS                     //
	//////////////////////////////////////////

	/**
	 * Helper function, that safely checks whether an array key exists
	 * and returns the trimmed value. If it doesn't exist, returns $default or null
	 *
	 * @param array $params
	 * @param string $key
	 * @param mixed|null $default
	 *
	 * @return mixed
	 */
	public static function paramGet( $params, $key, $default = null ) {
		if ( isset( $params[$key] ) ) {
			return trim( $params[$key] );
		} else {
			return $default;
		}
	}

	/**
	 * Debug function that converts an object/array to a <pre> wrapped pretty printed JSON string
	 *
	 * @param mixed $obj
	 * @return string
	 */
	public static function toJSON( $obj ) {
		header( 'Content-Type: application/json' );
		echo json_encode( $obj, JSON_PRETTY_PRINT );
		die();
	}

}
