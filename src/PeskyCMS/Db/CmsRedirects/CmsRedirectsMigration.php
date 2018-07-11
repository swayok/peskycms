<?php

namespace PeskyCMS\Db\CmsRedirects;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use PeskyCMF\Db\Admins\CmfAdminsTableStructure;
use PeskyCMS\Db\CmsPages\CmsPagesTableStructure;

class CmsRedirectsMigration extends Migration {

    public function up() {
        if (!\Schema::hasTable(CmsRedirectsTableStructure::getTableName())) {
            \Schema::create(CmsRedirectsTableStructure::getTableName(), function (Blueprint $table) {
                $table->increments('id');
                $table->integer('page_id')->nullable()->unsigned();
                $table->integer('admin_id')->nullable()->unsigned();
                $table->string('from_url', 500);
                $table->string('to_url', 500)->nullable();
                $table->boolean('is_permanent')->default(true);

                $table->timestampTz('created_at')->default(\DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(\DB::raw('NOW()'));

                $table->unique('from_url');

                $table->foreign('page_id')
                    ->references('id')
                    ->on(CmsPagesTableStructure::getTableName())
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
                $table->foreign('admin_id')
                    ->references('id')
                    ->on(CmfAdminsTableStructure::getTableName())
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }
    }

    public function down() {
        \Schema::dropIfExists(CmsRedirectsTableStructure::getTableName());
    }
}
