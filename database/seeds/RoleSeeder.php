<?php

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $roles=[
            [
                "name"=>"Super Admin",
                "display_name"=>"Super Admin",
                "description"=>"Super Admin"
            ],
            [
                "name"=>"Admin",
                "display_name"=>"Admin",
                "description"=>"Admin"
            ],
            [
                "name"=>"Vendor",
                "display_name"=>"Vendor",
                "description"=>"Vendor"
            ]
        ];
        foreach($roles as $role){
            $roleObj= new \App\Modal\Role([
                "name"=>$role['name'],
                "display_name"=>$role['display_name'],
                "description"=>$role['description']
            ]);
            $roleObj->save();
        }
    }
}
