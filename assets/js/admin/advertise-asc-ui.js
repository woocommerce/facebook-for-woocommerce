import React from 'react';
import ReactDOM from 'react-dom/client';
import MainView from './advertise-asc-components/main-view'
import InsightsView from './advertise-asc-components/insights-view'

function campaignCreationUILoader(rootElementId, props, reset) {
  const element = document.getElementById(rootElementId);
  if (element) {
    const root = ReactDOM.createRoot(element);
    root.render(
      <React.StrictMode>
        <>
          <MainView props={props} onFinish={reset} />
        </>
      </React.StrictMode>
    );
  }
}

function insightsUILoader(rootElementId, props) {
  const element = document.getElementById(rootElementId);
  if (element) {
    const root = ReactDOM.createRoot(element);
    root.render(
      <React.StrictMode>
        <>
          <InsightsView spend={props.spend} dailyBudget={props.dailyBudget} reach={props.reach} clicks={props.clicks} views={props.views} addToCarts={props.addToCarts} purchases={props.purchases} countryList={props.countryList} status={props.status} currency={props.currency} campaignType={props.campaignType} />
        </>
      </React.StrictMode>
    );
  }
}

window.campaignCreationUILoader = campaignCreationUILoader;
window.insightsUILoader = insightsUILoader;