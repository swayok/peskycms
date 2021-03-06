<?php

namespace PeskyCMS\Db\CmsPages;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use PeskyCMF\Db\Admins\CmfAdminsTableStructure;

class CmsPagesMigration extends Migration {

    public function up() {
        if (!\Schema::hasTable(CmsPagesTableStructure::getTableName())) {
            \Schema::create(CmsPagesTableStructure::getTableName(), function (Blueprint $table) {
                $table->increments('id');
                $table->integer('parent_id')->nullable()->unsigned();
                $table->integer('admin_id')->nullable()->unsigned();
                $table->string('type', 50)->default(CmsPage::TYPE_PAGE);
                $table->string('title', 500)->default('');
                $table->string('comment', 1000)->default('');
                $table->string('url_alias')->nullable();
                $table->string('page_code')->nullable();
                $table->string('meta_description', 1000)->default('');
                $table->string('meta_keywords', 500)->default('');
                $table->integer('position')->nullable();
                $table->boolean('with_contact_form')->default(false);
                $table->boolean('is_published')->default(true);
                $table->timestampTz('publish_at')->default(\DB::raw('NOW()'));
                $table->timestampTz('created_at')->default(\DB::raw('NOW()'));
                $table->timestampTz('updated_at')->default(\DB::raw('NOW()'));

                if (config('database.connections.' . config('database.default') . '.driver') === 'pgsql') {
                    $table->jsonb('texts');
                    $table->jsonb('custom_info');
                    $table->jsonb('images')->default('{}');
                } else {
                    $table->text('texts');
                    $table->text('custom_info');
                    $table->mediumText('images')->nullable();
                }

                $table->index('parent_id');
                $table->index('admin_id');
                $table->index('is_published');
                $table->unique(['url_alias', 'parent_id']);
                $table->unique('page_code');

                $table->foreign('parent_id')
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
        \Schema::dropIfExists(CmsPagesTableStructure::getTableName());
    }
}
