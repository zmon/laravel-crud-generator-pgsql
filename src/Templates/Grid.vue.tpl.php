<template>
    <div>
        <div v-if="global_error_message" class="alert alert-danger" role="alert">
            {{ global_error_message }}
        </div>
        <div v-if="server_message !== false" class="alert alert-danger" role="alert">
            {{ this.server_message}} <a v-if="try_logging_in" href="/login">Login</a>
        </div>
        <!-- Grid Actions Top -->
        <div class="grid-top row mb-0 align-items-center">
            <div class="col-lg-8 mb-2">
                <form class="form-inline mb-0">
                    <a v-if="params.CanAdd" href="#" @click.default="goToNew" class="btn btn-primary mb-3 mb-sm-2 mr-3">Add a
                        [[display_name_singular]]</a>
                    <search-form-group
                        class="mb-0"
                        :errors="form_errors.keyword"
                        label="Search"
                        labelFor="keyword">
                        <input
                            name="keyword"
                            id="field_keyword"
                            v-model="query"
                            @keyup="getData(1)"
                            class="form-control mb-2"
                            type="text"
                            placeholder="Filter by name">
                    </search-form-group>
                </form>
            </div>
            <div class="col-lg-4 text-lg-right mb-2">

            </div>
        </div><!-- end Grid Actions Top -->

        <!-- Grid -->
        <div class="grid no-more-tables table-responsive mb-4">
            <table class="table table-striped table-hover mb-0">
                <thead>
                <tr>
                    [[foreach:grid_columns]]
                    <ss-grid-column-header
                        v-on:selectedSort="sortColumn"
                        v-bind:selectedKey="sortKey"
                        v-bind:selectedOrder="sortOrder"
                        title="Sort by [[i.display]]"
                        :params="{
                            sortField: '[[i.name]]',
                            InitialSortOrder: 'asc',
                        }">
                        [[i.display]]

                    </ss-grid-column-header>
                    [[endforeach]]
                    <th style="width:20%;" class="text-lg-center">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-if="gridState == 'wait'">
                    <td colspan="[[number_of_grid_columns]]" class="grid-alert">
                        <div class="alert alert-info"
                             role="alert">Please wait.
                        </div>
                    </td>
                </tr>
                <tr v-if="gridState == 'error'">
                    <td colspan="[[number_of_grid_columns]]" class="grid-alert">
                        <div class="alert alert-warning"
                             role="alert">Error please try again.
                        </div>
                    </td>
                </tr>

                <tr v-if="gridState == 'good' && !gridData.length">
                    <td colspan="[[number_of_grid_columns]]" class="grid-alert">
                        <div class="alert alert-warning"
                             role="alert">No matching records found.
                        </div>
                    </td>
                </tr>

                <tr v-else v-for="row in this.gridData" :key="row.id">
                    [[foreach:grid_columns]]
                    [[if:i.name=='name']]
                    <td data-title="[[i.display]]">
                        <a v-bind:href="'/[[view_folder]]/' + row.id"
                           v-if="(params.CanShow == '1')">
                            {{ row.[[i.name]] }}
                        </a>
                        <span v-if="(params.CanShow != '1')">
                                        {{ row.[[i.name]] }}
                        </span>
                    </td>
                    [[endif]]
                    [[if:i.name!='name']]
                    <td data-title="[[i.display]]">{{ row.[[i.name]] }}</td>
                    [[endif]]
                    [[endforeach]]
                    <td data-title="Actions" class="text-lg-center text-nowrap">
                        <a v-bind:href="'/[[view_folder]]/' + row.id + '/edit'"
                           v-if="(params.CanEdit)"
                           class="grid-action-item">
                            Edit
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div><!-- end Grid -->

        <!-- Grid Actions Bottom -->
        <div class="grid-bottom row mb-0 align-items-center">
            <div class="col-lg-4 mb-2">
                <a v-if="(params.CanExcel)" href="/[[view_folder]]/download" class="btn btn-primary mb-2 mr-2">Export to Excel</a>
                <a v-if="(params.CanPdf)" href="/[[view_folder]]/print" class="btn btn-primary mb-2 mr-2">Print PDF</a>
            </div>
            <ss-grid-pagination class="col-lg-4 mb-2"
                                v-bind:current_page="current_page"
                                v-bind:last_page="last_page"
                                v-bind:total="total"
                                v-on:goto-page="getData(...arguments)">
            </ss-grid-pagination>
            <ss-grid-pagination-location class="col-lg-4 text-lg-right mb-2"
                                         v-bind:current_page="current_page"
                                         v-bind:last_page="last_page"
                                         v-bind:total="total">
            </ss-grid-pagination-location>
        </div><!-- end Grid Actions Bottom -->
    </div>
</template>

<script>

    import SsGridColumnHeader from "../SS/SsGridColumnHeader";
    import SsGridPagination from "../SS/SsGridPagination";
    import SsGridPaginationLocation from "../SS/SsPaginationLocation";

    export default {
        name: '[[view_folder]]-grid',
        components: {SsGridColumnHeader, SsGridPaginationLocation, SsGridPagination},
        props: {
            'params': {
                type: Object,
                default: function () {
                }
            }
        },

        mounted: function () {

            this.current_page = (!isNaN(parseInt(this.params.Page))) ? parseInt(this.params.Page) : 1;
            this.query = this.params.Search;
            this.getData(this.current_page);
        },

        data: function () {

            return {
                gridState: 'wait',
                query: this.params.Search,
                gridData: [],
                current_page: 1,
                last_page: null,
                total: null,

                sortOrder: this.params.sortOrder,
                sortKey: this.params.sortKey,

                global_error_message: null,


                form_errors: {
                    page: false,
                    keyword: false,
                    column: false,
                    direction: false,
                },
                server_message: false,
                try_logging_in: false,
            }
        },

        methods: {

            goToNew: function () {
                window.location.href = '/[[view_folder]]/create';
            },

            sortColumn: function (obj) {
                this.sortKey = obj.sortField;
                this.sortOrder = obj.sortOrder;
                this.getData(1);
            },

            getData: function (new_page_number) {

                this.global_error_message = null;

                let getPage;

                getPage = this.getDataUrl(new_page_number) +
                    '&column=' + this.sortKey +
                    '&direction=' + this.sortOrder;

                this.gridData = [];
                this.gridState = 'wait';

                if (getPage != null) {    // We have a filter
                    axios.get(getPage)
                        .then(response => {
                            if (response.status === 200) {
                                Object.keys(this.form_errors).forEach(i => this.form_errors[i] = false);
                                this.gridData = response.data.data;
                                this.total = response.data.total;
                                this.current_page = response.data.current_page;
                                this.last_page = (response.data.last_page || 1);
                            } else {
                                this.server_message = res.status;
                            }
                            this.gridState = 'good';
                        }).catch(error => {
                        if (error.response) {
                            this.gridState = 'error';
                            if (error.response.status === 422) {
                                // Clear errors out
                                Object.keys(this.form_errors).forEach(i => this.form_errors[i] = false);
                                // Set current errors
                                Object.keys(error.response.data.errors).forEach(i => this.form_errors[i] = error.response.data.errors[i]);
                            } else if (error.response.status === 404) {  // Record not found
                                this.server_message = 'Record not found';
                                window.location = '/[[route_path]]';
                            } else if (error.response.status === 419) {  // Unknown status
                                this.server_message = 'Unknown Status, please try to ';
                                this.try_logging_in = true;
                            } else if (error.response.status === 500) {  // Unknown status
                                this.server_message = 'Server Error, please try to ';
                                this.try_logging_in = true;
                            } else {
                                this.server_message = error.response.data.message;
                            }
                        } else {
                            console.log(error.response);
                            this.server_message = error;
                        }
                    });
                }
            },

            getDataUrl: function (new_page_number) {

                var url = 'api-[[view_folder]]?';
                var queryParams = [];

                queryParams.push('page=' + new_page_number);

                if (this.isDefined(this.query) && (this.query.trim().length > 0)) queryParams.push('keyword=' + this.query);

//                if (this.isDefined(this.searchType)) queryParams.push('search_type=' + this.searchType);
//                if (this.isDefined(this.showFilter)) queryParams.push('show_filter=' + this.showFilter);
//                if (this.isDefined(this.contractorSelected)) queryParams.push('contractor_id=' + this.contractorSelected);

                if (queryParams.length > 0) url += queryParams.join('&');

                return url;
            }
        }
    }

</script>
