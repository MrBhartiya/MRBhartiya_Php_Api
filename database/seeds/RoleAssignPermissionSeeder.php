<?php

use Illuminate\Database\Seeder;

class RoleAssignPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $roles=\App\Modal\Role::get();
        foreach ($roles as $role){
            $permissions=\App\Modal\Permission::get();
            foreach ($permissions as $permission){
                if($role->name=="Vendor" && $permission->name=='vendor_dashboard'){
                    \App\Modal\PermissionRole::create([
                        "permission_id"=>$permission->id,
                        "role_id"=>$role->id
                    ]);
                }else if($role->name=="Admin" && $permission->name!='setting' && $permission->name!='vendor_dashboard' && $permission->name!='vendor_report' && $permission->name!='vendor' && $permission->name!='student' && $permission->name!='company'){
                    \App\Modal\PermissionRole::create([
                        "permission_id"=>$permission->id,
                        "role_id"=>$role->id
                    ]);
                }else if($role->name=="Super Admin" && $permission->name!='vendor_dashboard'){
                    \App\Modal\PermissionRole::create([
                        "permission_id"=>$permission->id,
                        "role_id"=>$role->id
                    ]);
                }
            }
        }
    }
}
