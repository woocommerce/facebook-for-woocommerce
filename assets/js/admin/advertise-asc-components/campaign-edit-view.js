import React, { useState } from 'react';
import { Card, Col, Form, Input, InputNumber, Row, Select, Space, Switch, Tooltip } from 'antd';
import { DollarOutlined, EditOutlined, GlobalOutlined, PoweroffOutlined, QuestionCircleFilled } from '@ant-design/icons'
import { CountryList } from './eligible-country-list'

const ProspectingViewComponent = (props) => {
    const countryPairs = CountryList.map((c) => {
        return {
            key: Object.keys(c)[0],
            value: Object.values(c)[0]
        }
    });

    const [selectedCountries, setSelectedCountries] = useState(countryPairs.filter(c => { return props.selectedCountries.indexOf(c.key) >= 0 }));

    const availableOptions = countryPairs.filter(x => { return selectedCountries.indexOf(x['key']) == -1; });

    return (
        <>
            <p style={{ color: '#1d2327', margin: '10px 0 0 0', width: '250px' }}>
                <GlobalOutlined style={{ color: '#1d2327' }} /> {' '} Country {' '} <Tooltip title="Countries where your campaign will be shown."><QuestionCircleFilled style={{ fontSize: '75%', alignContent: 'center' }} /></Tooltip>
            </p>
            <div style={{ background: 'transparent', width: '250px' }}>
                <Form.Item
                    style={{ margin: 0, padding: 0, width: '250px', background: 'transparent' }}
                    label=''
                    name='n1'
                    initialValue={selectedCountries}
                    rules={[
                        {
                            required: true,
                            message: 'You should select at least one country',
                        }
                    ]}>
                    <Select mode="multiple"
                        maxTagCount={5}
                        onChange={(c) => { 
                            const countries = countryPairs.filter( (p) => c.indexOf(p.key) >= 0);
                            setSelectedCountries(countries); 
                            props.onCountryListChange(countries);
                            }}
                        style={{ marginTop: 0, width: '100%', maxWidth: '200px', maxHeight: '150px', whiteSpace: 'nowrap', overflow: 'auto' }}
                        options={availableOptions.map((item) => ({
                            value: item['key'],
                            label: item['value']
                        }))} />
                </Form.Item>
            </div>
        </>);
};

const CampaignEditView = (props) => {

    const title = props.campaignType == 'retargeting' ? 'Retargeting Campaign' : 'New Customers Campaign';
    const subtitle = props.campaignType == 'retargeting' ? "Bring back visitors who visited your website and didn't complete their purchase" : "Reach out to potential new buyers for your products";
    const { TextArea } = Input;
    const [message, setMessage] = useState(props.message);
    const [dailyBudget, setDailyBudget] = useState(props.dailyBudget);
    const [status, setStatus] = useState(props.currentStatus);
    const minDailyBudget = parseFloat(props.minDailyBudget);
    
    return (
        <Card>
            <Row>
                <Col>
                    <h1 style={{ fontSize: "23px", fontWeight: 400, margin: 0, padding: "9px 0 4px", lineHeight: "1.3" }}>{title}</h1>
                    <p style={{ marginTop: 0, marginBottom: '20px', color: "#949494", boxSizing: 'border-box' }}>{subtitle}</p>
                </Col>
                <Col></Col>
            </Row>
            <Row>
                <Col>
                </Col>
                <Col>
                    <Space direction='vertical'>
                        <p style={{ color: '#1d2327', margin: 0 }}>
                            <PoweroffOutlined style={{ color: '#1d2327' }} /> {' '} Campaign Off/On {' '} <Tooltip title="Do you want your campaign to actively run? Make sure to select 'On'"><QuestionCircleFilled style={{ fontSize: '75%', alignContent: 'center' }} /></Tooltip>
                        </p>
                        <div style={{ margin: 0, padding: 0 }}>
                            <Form.Item 
                                style={{ margin: 0, padding: 0 }}
                                label=''
                                name='n4'
                                >
                                <Switch
                                    checked={status}
                                    style={{ margin: 0, padding: 0 }}
                                    checkedChildren="On"
                                    unCheckedChildren="Off"
                                    onChange={(e) => { setStatus(e); props.onStatusChange(e);}}
                                    />
                            </Form.Item>
                        </div>
                        

                        <p style={{ color: '#1d2327', margin: '10px 0 0 0' }}>
                            <DollarOutlined style={{ color: '#1d2327' }} /> {' '} Daily Budget {' '} <Tooltip title="How much would you want to spend on a daily basis?"><QuestionCircleFilled style={{ fontSize: '75%', alignContent: 'center' }} />
                            </Tooltip>
                        </p>
                        <div style={{ margin: 0, padding: 0, width:'200px' }}>
                            <Form.Item
                                style={{ margin: 0, padding: 0 }}
                                label=''
                                name='n2'
                                initialValue={dailyBudget}
                                rules={[
                                    {
                                        required: true,
                                        message: 'Daily budget must be set',
                                    },
                                    {
                                        message: 'Minimum allowed daily budget is ' + props.minDailyBudget + ' ' + props.currency,
                                        validator: (_, value) => {
                                            console.log(value);
                                            console.log('validator');
                                            if (value && (parseFloat(value) < minDailyBudget)) {
                                                return Promise.reject();
                                            } else {
                                                return Promise.resolve();
                                            }
                                        }
                                    }
                                ]}>
                                <InputNumber
                                    style={{
                                        width: 200,
                                        margin: 0
                                    }}
                                    key={1}
                                    step="0.1"
                                    onChange={(e) => { props.onDailyBudgetChange(e); setDailyBudget(e); }}
                                    stringMode
                                    addonAfter={props.currency}
                                />
                            </Form.Item>
                        </div>
                        {props.showCountry ? (
                            <ProspectingViewComponent onCountryListChange={props.onCountryListChange} selectedCountries={props.selectedCountries} />
                        ) : <></>}

                    </Space>
                </Col>
                <Col style={{ marginLeft: "40px" }}>
                    <Space direction='vertical'>
                        <div style={{ display: 'inline-block', margin: 0, padding: 0, margin: '4px 0 0 0' }}>
                            <p style={{ color: '#1d2327', marginLeft: '10px;', marginTop: 0, marginBottom: 0 }}>
                                <EditOutlined style={{ color: '#1d2327' }} /> {' '} Customize your message <Tooltip title="The pitch for selling your products. Choose it wisely!"><QuestionCircleFilled style={{ fontSize: '75%', alignContent: 'center' }} /></Tooltip>
                            </p>
                            <p style={{ margin: 0, padding: 0, color: "#949494", fontSize: '75%' }} >{'The carousel will show your products'}</p>
                        </div>
                        <div style={{ margin: '4px 0 0 0', position: 'relative', borderColor: '#5a5a5a', borderWidth: '0.5px', borderStyle: 'solid', background: 'transparent', width: '100%', height: '100%' }}>
                            <img style={{ display: 'block', top: '20px', width: '300px', marginLeft: 0, marginBottom: '5px' }} src={require('!!url-loader!./../../../images/woo_post1.png').default} />
                            <Form.Item
                                label=''
                                name='n3'
                                initialValue={message}
                                rules={[
                                    {
                                        required: true,
                                        message: 'Ad message must be set.',
                                    }
                                ]}>
                                <TextArea style={{ width: '300px', marginLeft: '5px', marginRight: '5px', resize: 'none' }} compact={true} onChange={(e) => { props.onMessageChange(e.target.value); setMessage(e.target.value); }}></TextArea>
                            </Form.Item>
                            <img style={{ display: 'block', width: '300px', marginLeft: 0, marginRight: '5px', marginTop: '20px', marginBottom: '5px', marginRight: 0 }} src={require('!!url-loader!./../../../images/woo_post2.png').default} />
                        </div>
                    </Space>
                </Col>
            </Row>

        </Card >
    );
};

export default CampaignEditView;