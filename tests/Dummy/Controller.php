<?php

namespace NicoAndra\OpenApiGenerator\Test;

use Illuminate\Routing\Controller as LaravelController;
use NicoAndra\OpenApiGenerator\Attributes;
use Spatie\LaravelData\DataCollection;

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

    #[Attributes\Description('This is the method description')]
    public function stringParameter(string $parameter): ReturnData
    {
        return ReturnData::create($parameter);
    }

    #[Attributes\Description('This is the multiResponse description')]
    public function multiResponse(string $parameter): ReturnData|ReturnDataWithStatusAttribute
    {
        return ReturnData::create($parameter);
    }

    public function modelParameter(Model $parameter): ReturnData
    {
        return ReturnData::create($parameter);
    }

    #[Attributes\Summary('This is a summary')]
    public function requestBasic(RequestData $request): ReturnData
    {
        return ReturnData::create($request);
    }

    public function requestNoData(NotData $request): ReturnData
    {
        return ReturnData::create($request);
    }

    #[Attributes\Description('Summary of allCombined')]
    public function allCombined(
        #[Attributes\Example('example value for parameter_1')]
        int $parameter_1,
        #[Attributes\Example('example value for parameter_2')]
        string $parameter_2,
        #[Attributes\Example('example value for parameter_3')]
        Model $parameter_3,
        #[Attributes\Example('example value for request')]
        RequestData $request
    ): ReturnData {
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
