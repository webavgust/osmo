<?php

namespace App\Modules\Pub\Company\Repositories;

use App\Modules\Pub\Country\Models\Country;
use App\Modules\Pub\Log\Models\Log;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Company\Services\CompanyListFilterService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\Company\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanyRepository
{
    public static function create(\Illuminate\Http\Request $request)
    {
        $data = $request->all();
        $company = Company::make([
            'name' => $data['name'],
        ]);

        $company->sector()->associate(Sector::findOrFail($data['sector']));
        $company->partner()->associate(Partner::findOrFail($data['partner']));
        $company->country()->associate(Country::findOrFail($data['country']));
        $company->save();

        return $company;
    }

    public static function update(Company $company, \Illuminate\Http\Request $request)
    {
        $data = $request->all();
        $company->update([
            'name' => $data['name'],
        ]);

        $company->sector()->associate(Sector::findOrFail($data['sector']));
        $company->partner()->associate(Partner::findOrFail($data['partner']));
        $company->country()->associate(Country::findOrFail($data['country']));
        $company->save();
    }

    public static function delete(Company $company)
    {
        $company->delete();
    }


    public static function getAll()
    {
        return Company::all()->keyBy('id');
    }

    public static function getByID(mixed $id)
    {
        if(is_array($id)) {
            return Company::whereIn('id', $id)->get();
        } else {
            return Company::find($id);
        }
    }

    public static function get(mixed $company_id)
    {
        return Company::findOrFail($company_id);
    }


    /**
     * Получение данных для таблицы
     *
     * @param $params
     * @return array
     */
    public function getTable($params = [])
    {
        $filterService = new CompanyListFilterService($params['_token'] ?? null);
        $builder = Company::where('companies.id', '>', 0);

        $builder_full = clone $builder;

        # Filter
        $builder = $filterService->filter($builder);
        $count = $count_filtered = $builder->count();

        # Search
        if (!empty($params['search'])) {
            $builder->search($params['search']);
            $count_filtered = $builder->count();
        }

        if (!empty($params['sort']) && !empty($params['order'])) {
            $builder->orderBy($params['sort'], $params['order']);
        } else {
            $builder->orderBy('active','desc')->orderBy('name');
        }

        $builder->with(['sector', 'partner', 'country']);

        # Paginate
        if (!empty($params['limit']))
            $builder->limit($params['limit']);

        if (!empty($params['offset']))
            $builder->skip($params['offset']);

        return [
            'count' => $count,
            'count_filter' => $count_filtered,
            'rows' => $builder->get(),
            'filter' => [
                'sector' => $builder_full->pluck('sector_id')->unique()->toArray(),
            ]
        ];
    }

}
