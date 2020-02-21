<template>
    <form @submit.prevent="handleSubmit" class="form-horizontal">
        <div class="card">
            <div class="card-header align-middle">
                <h1>
                    <span v-if="form_data.id">
                        Edit {{record.name}}
                    </span>
                    <span v-else>
                        Add a [[display_name_singular]]
                    </span>

                </h1>
            </div>
            <div class="card-body">

                <div v-if="server_message !== false" class="alert alert-danger" role="alert">
                    {{ this.server_message}} <a v-if="try_logging_in" href="/login">Login</a>
                </div>
                [[foreach:edit_columns]]
                [[if:i.name=='name']]

                <div class="row">
                    <div class="col-md-12">
                        <std-form-group label="[[i.display]]" label-for="[[i.name]]" :errors="form_errors.[[i.name]]"
                                        :required="true">
                            <fld-input
                                name="[[i.name]]"
                                v-model="form_data.[[i.name]]"
                                required
                            />
                            <template slot="help">
                                Name must be unique.
                            </template>
                        </std-form-group>
                    </div>
                </div>
                [[endif]]
                [[if:i.name!='name']]

                <div class="row">
                    <div class="col-md-12">
                        <std-form-group label="[[i.display]]" label-for="[[i.name]]" :errors="form_errors.[[i.name]]">
                            <fld-input
                                name="[[i.name]]"
                                v-model="form_data.[[i.name]]"
                            />
                        </std-form-group>
                    </div>
                </div>
                [[endif]]
                [[endforeach]]

            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary" :disabled="processing">
                            <span v-if="this.form_data.id">Change [[display_name_singular]]</span>
                            <span v-else="this.form_data.id">Add [[display_name_singular]]</span>
                        </button>
                    </div>
                    <div class="col-md-6 text-md-right mt-2 mt-md-0">
                        <a :href="this.cancel_url" class="btn btn-default">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<script>
    import axios from 'axios';

    export default {
        name: "[[route_path]]-form",
        props: {
            record: {
                type: [Boolean, Object],
                default: false,
            },
            cancel_url: {
                type: [String],
                default: '/[[model_singular]]',
            },
            csrf_token: {
                type: String,
                default: ''
            },
        },
        data() {
            return {
                form_data: {
                    // _method: 'patch',
                    _token: this.csrf_token,
[[foreach:columns]]
  [[if:i.type=='id']]
                    [[i.name]]: 0,
                [[endif]]
  [[if:i.type=='text']]
                    [[i.name]]: '',
                [[endif]]
  [[if:i.type=='number]]
                    [[i.name]]: 0,
                [[endif]]
  [[if:i.type=='date']]
                    [[i.name]]: null,
                [[endif]]
  [[if:i.type=='unknown']]
                    [[i.name]]: '',
                [[endif]]
                    [[endforeach]]
        },
                form_errors: {
[[foreach:columns]]
                [[i.name]]: false,
                    [[endforeach]]
                },
                server_message: false,
                try_logging_in: false,
                processing: false,
        }
        },
        mounted() {
            if (this.record !== false) {
                // this.form_data._method = 'patch';
                Object.keys(this.record).forEach(
                    i => (this.$set(this.form_data, i, this.record[i]))
                )
            } else {
                // this.form_data._method = 'post';
            }

        },
        methods: {
            async handleSubmit() {

                this.server_message = false;
                this.processing = true;
                let url = '';
                let amethod = '';
                if (this.form_data.id) {
                    url = '/[[route_path]]/' + this.form_data.id;
                    amethod = 'put';
                } else {
                    url = '/[[route_path]]';
                    amethod = 'post';
                }
                await axios({
                    method: amethod,
                    url: url,
                    data: this.form_data
                })
                    .then((res) => {
                        if (res.status === 200) {
                            window.location = '/[[route_path]]';
                        } else {
                            this.server_message = res.status;
                        }
                    }).catch(error => {
                        if (error.response) {
                            if (error.response.status === 422) {
                                // Clear errors out
                                Object.keys(this.form_errors).forEach(
                                    i => (this.$set(this.form_errors, i, false))
                                );
                                // Set current errors
                                Object.keys(error.response.data.errors).forEach(
                                    i => (this.$set(this.form_errors, i, error.response.data.errors[i]))
                                );
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
                        this.processing = false;
                    });
            }
        },
    }
</script>

