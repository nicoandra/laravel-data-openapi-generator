<?php

namespace NicoAndra\OpenApiGenerator\Test;

use Illuminate\Routing\Controller as LaravelController;
use Spatie\LaravelData\DataCollection;
use NicoAndra\OpenApiGenerator\Attributes\Description;
use NicoAndra\OpenApiGenerator\Attributes\Summary;
class Controller extends LaravelController
{
    public function noResponse() {}

    public function basic(): ReturnData
    {
        return new ReturnData();
    }

    /**
     * @return \NicoAndra\OpenApiGenerator\Test\ReturnData[]
     */
    public function array(): array
    {
        return [];
    }

    /**
     * @return ReturnData[]
     */
    public function arrayIncompletePath(): array
    {
        return [];
    }

    public function arrayFail(): array
    {
        return [];
    }

    /**
     * @return DataCollection<int,\NicoAndra\OpenApiGenerator\Test\ReturnData>
     */
    public function collection(): DataCollection
    {
        return ReturnData::collect([], DataCollection::class);
    }

    /**
     * @return DataCollection<int,ReturnData>
     */
    public function collectionIncompletePath(): DataCollection
    {
        return ReturnData::collect([], DataCollection::class);
    }

    public function collectionFail(): DataCollection
    {
        return ReturnData::collect([], DataCollection::class);
    }

    public function intParameter(int $parameter): ReturnData
    {
        return ReturnData::create($parameter);
    }

    #[Description('This is the method description')]
    public function stringParameter(string $parameter): ReturnData|ReturnDataWithStatusAttribute
    {
        return ReturnData::create($parameter);
    }

    public function modelParameter(Model $parameter): ReturnData
    {
        return ReturnData::create($parameter);
    }

    #[Summary('This is a summary')]
    public function requestBasic(RequestData $request): ReturnData
    {
        return ReturnData::create($request);
    }

    public function requestNoData(NotData $request): ReturnData
    {
        return ReturnData::create($request);
    }

    /**
     * Summary of allCombined
     * 
     */
    public function allCombined(int $parameter_1, string $parameter_2, Model $parameter_3, RequestData $request): ReturnData
    {
        return ReturnData::create($parameter_1, $parameter_2, $parameter_3, $request);
    }

    public function contentType(ContentTypeData $data): ContentTypeData
    {
        return ContentTypeData::create($data);
    }

    public function routeWithRouteParameter(RequestDataWithRouteParameter $requestData): ReturnData
    {
        return ReturnData::from(['message' => $requestData->routeParameter]);
    }


    public function routeWithStatusAttribute(RequestDataWithRouteParameter $requestData): ReturnDataWithStatusAttribute
    {
        return ReturnDataWithStatusAttribute::create();
    }
}
