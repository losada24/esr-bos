<?php

namespace App\Http\Requests;

use App\Enum\ExternalProductEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use Illuminate\Validation\Rule;
use App\Enum\GlassTypeEnum;
use App\Enum\ProductSystemEnum;
use App\Models\ExternalProductConfiguration;
use App\Rules\ValidateMullionHeight;
use App\Rules\ValidateMuntingHorizontalLines;
use App\Rules\ValidateMuntingVerticalLines;
use App\Rules\ValidateMuntinStyle;
use App\Traits\ExternalProductTrait;
use Illuminate\Auth\Events\Validated;

class UpdateMullionRequest extends FormRequest
{
    use ExternalProductTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
          'id' => 'required|exists:products,id',
          'mark' => 'required|string|max:255',
          'config' => [
            'required',
            'string',
            Rule::in(array_column(
              $this->getExtraMullionFields(
                ExternalProductConfiguration::where('external_product', ExternalProductEnum::$MULLION)->get()
              ), 'configuration'))
          ],
          'width' => 'required|numeric|min:1',
          'height' => [
            'required',
            'numeric',
            'min:26',
            new ValidateMullionHeight()
          ],
          'qty' => 'required|numeric|min:1',
          'markup' => 'required|numeric|min:0',
          'frame_color' => [
            'required',
            Rule::in(array_values(FrameColorEnum::$FRAME_COLOR))
          ],
          'order_id' => 'required|exists:orders,id',
        ];
    }
}
