<?php

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public static function run()
    {
        //
        $permissions=[
            [
                "name"=>"dashboard",
                "display_name"=>"Dashboard",
                "description"=>""
            ],
            [
                "name"=>"setting",
                "display_name"=>"Setting",
                "description"=>"Setting"
            ],
            [
                "name"=>"classes",
                "display_name"=>"Classes",
                "description"=>"Classes"
            ],
            [
                "name"=>"teacher",
                "display_name"=>"Teacher",
                "description"=>"Teacher"
            ],
            [
                "name"=>"student",
                "display_name"=>"Student",
                "description"=>"Student"
            ],
            [
                "name"=>"subject",
                "display_name"=>"Subject",
                "description"=>"Subject"
            ],
            [
                "name"=>"chapter",
                "display_name"=>"Chapter",
                "description"=>"Chapter"
            ],
            [
                "name"=>"topic",
                "display_name"=>"Topic",
                "description"=>"Topic"
            ],
            [
                "name"=>"video",
                "display_name"=>"Video",
                "description"=>"Video"
            ],
            [
                "name"=>"subscription",
                "display_name"=>"Subscription",
                "description"=>"Subscription"
            ],
            [
                "name"=>"quiz",
                "display_name"=>"Quiz",
                "description"=>"Quiz"
            ],
            [
                "name"=>"change_password",
                "display_name"=>"Change password",
                "description"=>"Change password"
            ],
            [
                "name"=>"vendor_dashboard",
                "display_name"=>"Vendor Dashboard",
                "description"=>"Vendor Dashboard"
            ],
            [
                "name"=>"notification",
                "display_name"=>"Notification",
                "description"=>"Notification"
            ],
            [
                "name"=>"company",
                "display_name"=>"Company",
                "description"=>"Company"
            ],
            [
                "name"=>"vendor",
                "display_name"=>"vendor",
                "description"=>"vendor"
            ],
            [
                "name"=>"vendor_report",
                "display_name"=>"Vendor Report",
                "description"=>"Vendor Report"
            ],

        ];
        foreach($permissions as $permission){
            $permission= new \App\Modal\Permission([
                "name"=>$permission['name'],
                "display_name"=>$permission['display_name'],
                "description"=>$permission['description'],
            ]);
            $permission->save();
        }
    }
}
